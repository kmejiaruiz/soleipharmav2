<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BranchTransfer;
use App\Models\BranchTransferItem;
use App\Models\Product;
use App\Models\User;
use App\Models\InventoryLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class BranchTransferController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->get('status', 'all');
        $user = Auth::user();

        $transfers = BranchTransfer::with(['items.product', 'creator:id,username', 'receiver:id,username'])
            ->when($status !== 'all', fn($q) => $q->where('status', $status))
            ->when(!$user->isSuperAdmin(), function ($q) use ($user) {
                if ($user->branch) {
                    $q->where(function ($sub) use ($user) {
                        $sub->where('from_branch', $user->branch)
                            ->orWhere('to_branch', $user->branch);
                    });
                } else {
                    $q->whereRaw('1 = 0');
                }
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        // Obtener lista dinámica de sucursales activas registradas en la base de datos
        $branches = \App\Models\Branch::orderBy('name')->pluck('name')->toArray();
        if (empty($branches)) {
            $branches = ['Sucursal Managua', 'Sucursal Leon'];
        }

        // Productos disponibles de la sucursal de origen inicial
        $initialBranch = $user->branch ?: ($branches[0] ?? 'Sucursal Managua');
        $products = Product::where('branch', $initialBranch)->where('available', true)->get(['id', 'name', 'stock', 'price']);

        return Inertia::render('Admin/BranchTransfers/Index', [
            'transfers'     => $transfers,
            'products'      => $products,
            'branches'      => $branches,
            'filters'       => ['status' => $status],
            'userIsSuperAdmin' => $user->isSuperAdmin(),
        ]);
    }

    // Endpoint para obtener productos según la sucursal seleccionada
    public function productsByBranch(Request $request)
    {
        $request->validate(['branch' => 'required|string']);
        $products = Product::where('branch', $request->branch)
            ->where('available', true)
            ->get(['id', 'name', 'stock', 'price']);
        return response()->json($products);
    }

    public function show(BranchTransfer $transfer)
    {
        $user = Auth::user();
        if (!$user->isSuperAdmin() && $user->branch && $transfer->from_branch !== $user->branch && $transfer->to_branch !== $user->branch) {
            abort(403, 'No tienes permiso para ver traslados de otra sucursal.');
        }

        $transfer->load([
            'items.product:id,name,barcode,sku,cost,price',
            'creator:id,username,name,first_name,last_name',
            'receiver:id,username,name,first_name,last_name'
        ]);

        return Inertia::render('Admin/BranchTransfers/Show', [
            'transfer' => $transfer,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'from_branch'        => 'required|string|max:255',
            'to_branch'          => 'required|string|max:255|different:from_branch',
            'notes'              => 'nullable|string',
            'items'              => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity'   => 'required|integer|min:1',
        ], [
            'to_branch.different' => 'La sucursal de destino debe ser diferente a la sucursal de origen.',
        ]);

        DB::beginTransaction();
        try {
            // Non-superadmin solo puede enviar desde su propia sucursal
            $user = Auth::user();
            if (!$user->isSuperAdmin() && $user->branch && $validated['from_branch'] !== $user->branch) {
                return redirect()->back()->withErrors(['from_branch' => 'No tienes permiso para enviar desde esta sucursal.']);
            }

            // Verificar stock en origen
            foreach ($validated['items'] as $item) {
                $product = Product::lockForUpdate()->find($item['product_id']);
                if ($product->stock < $item['quantity']) {
                    DB::rollBack();
                    return redirect()->back()->withErrors(['items' => "Stock insuficiente en {$validated['from_branch']} para: {$product->name}"]);
                }
            }

            $transfer = BranchTransfer::create([
                'from_branch' => $validated['from_branch'],
                'to_branch'   => $validated['to_branch'],
                'status'      => 'pendiente',
                'notes'       => $validated['notes'] ?? null,
                'created_by'  => Auth::id(),
            ]);

            foreach ($validated['items'] as $item) {
                BranchTransferItem::create([
                    'transfer_id'   => $transfer->id,
                    'product_id'    => $item['product_id'],
                    'quantity_sent' => $item['quantity'],
                ]);

                // Descontar de la sucursal de origen
                $originProduct = Product::find($item['product_id']);
                if ($originProduct) {
                    $prevStock = (int) $originProduct->stock;
                    $newStock  = $prevStock - (int) $item['quantity'];
                    $originProduct->decrement('stock', $item['quantity']);

                    InventoryLog::create([
                        'product_id'     => $originProduct->id,
                        'admin_id'       => Auth::id(),
                        'admin_name'     => Auth::user()->username,
                        'branch'         => $validated['from_branch'],
                        'change_type'    => 'stock_decrease',
                        'previous_stock' => $prevStock,
                        'new_stock'      => $newStock,
                        'description'    => "Envío por traslado #TR-{$transfer->id} hacia {$transfer->to_branch}. Despachado: {$item['quantity']} uds.",
                    ]);
                }
            }

            DB::commit();
            return redirect()->back()->with('success', 'Traslado a sucursal registrado exitosamente.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function validateAuth(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $authorizingUser = User::verifyAdminCredentials($request->username, $request->password);

        if (!$authorizingUser) {
            return response()->json([
                'success' => false,
                'message' => 'Credenciales inválidas o no autorizadas.',
            ], 422);
        }

        // 3. Validar sucursal:
        // "si la sesion pertenece a un superadmin u admin de sucursal leon y se ingresan otras credencuales de ej sucursal managua el sistema lance una alerta que diga: 'Este usuario no tiene permisos en esta sucursal'"
        $normalize = function ($str) {
            $s = strtolower(trim($str ?? ''));
            $s = str_replace(['á', 'é', 'í', 'ó', 'ú'], ['a', 'e', 'i', 'o', 'u'], $s);
            return $s;
        };

        $sessionUser   = Auth::user();
        $sessionBranch = $normalize($sessionUser->branch);
        $authBranch    = $normalize($authorizingUser->branch);

        if (!empty($sessionBranch) && !empty($authBranch) && $sessionBranch !== $authBranch) {
            return response()->json([
                'success' => false,
                'message' => 'Este usuario no tiene permisos en esta sucursal',
            ], 422);
        }

        return response()->json([
            'success'  => true,
            'user_id'  => $authorizingUser->id,
            'username' => $authorizingUser->username,
            'name'     => $authorizingUser->name ?? ($authorizingUser->first_name ? trim("{$authorizingUser->first_name} {$authorizingUser->last_name}") : $authorizingUser->username),
        ]);
    }

    public function receive(Request $request, BranchTransfer $transfer)
    {
        $user = Auth::user();

        // Normalizar nombres de sucursal para comparación
        $normalize = function ($str) {
            $s = strtolower(trim($str ?? ''));
            $s = str_replace(['á', 'é', 'í', 'ó', 'ú'], ['a', 'e', 'i', 'o', 'u'], $s);
            return $s;
        };

        $userBranch = $normalize($user->branch);
        $toBranch   = $normalize($transfer->to_branch);

        $isDestinationBranch = ($userBranch === $toBranch) ||
            (!empty($userBranch) && !empty($toBranch) && (str_contains($userBranch, $toBranch) || str_contains($toBranch, $userBranch)));

        $isAdminOrSuperadmin = in_array($user->role, ['admin', 'superadmin']);

        // Únicamente los usuarios ADMIN o SUPERADMIN de la sucursal de DESTINO (o Superadmin) pueden recibir este traslado
        if (!$isAdminOrSuperadmin || (!$user->isSuperAdmin() && !$isDestinationBranch)) {
            return redirect()->back()->withErrors([
                'branch' => "Únicamente usuarios Admin o Superadmin asignados a {$transfer->to_branch} pueden recibir este traslado."
            ]);
        }

        if ($transfer->status !== 'pendiente') {
            return redirect()->back()->withErrors(['status' => 'Este traslado ya fue procesado.']);
        }

        $validated = $request->validate([
            'items'                     => 'required|array|min:1',
            'items.*.id'                => 'required|exists:branch_transfer_items,id',
            'items.*.quantity_received' => 'required|integer|min:0',
            'reception_notes'           => 'nullable|string',
        ], [
            'items.*.quantity_received.required' => 'Debes ingresar la cantidad recibida para cada producto.',
            'items.*.quantity_received.min'      => 'La cantidad recibida no puede ser menor a 0.',
        ]);

        DB::beginTransaction();
        try {
            $notes = $transfer->notes;
            if (!empty($validated['reception_notes'])) {
                $notes = $notes
                    ? ($notes . " | Nota Recepción: " . $validated['reception_notes'])
                    : ("Nota Recepción: " . $validated['reception_notes']);
            }

            $transfer->update([
                'status'      => 'recibido',
                'received_by' => $request->input('authorizing_user_id') ?: Auth::id(),
                'received_at' => now(),
                'notes'       => $notes,
            ]);

            $receivedMap = collect($validated['items'])->keyBy('id');

            foreach ($transfer->items as $item) {
                $recvQty = isset($receivedMap[$item->id]) 
                    ? (int)$receivedMap[$item->id]['quantity_received'] 
                    : (int)$item->quantity_sent;

                $item->update([
                    'quantity_received' => $recvQty,
                ]);

                // Acreditar al inventario de la sucursal de DESTINO únicamente lo recibido físicamente
                if ($recvQty > 0) {
                    $originProduct = Product::find($item->product_id);
                    if ($originProduct) {
                        // Buscar el registro del producto en la sucursal de destino
                        $destProduct = null;
                        if (!empty($originProduct->barcode)) {
                            $destProduct = Product::where('branch', $transfer->to_branch)
                                ->where('barcode', $originProduct->barcode)
                                ->first();
                        }
                        if (!$destProduct) {
                            $destProduct = Product::where('branch', $transfer->to_branch)
                                ->where('name', $originProduct->name)
                                ->first();
                        }
                        // Si no existiera en la sucursal destino, clonar el producto
                        if (!$destProduct) {
                            $destProduct = $originProduct->replicate();
                            $destProduct->branch = $transfer->to_branch;
                            $destProduct->stock  = 0;
                            $destProduct->save();
                        }

                        $prevStock = (int) $destProduct->stock;
                        $newStock  = $prevStock + $recvQty;
                        $destProduct->increment('stock', $recvQty);

                        InventoryLog::create([
                            'product_id'     => $destProduct->id,
                            'admin_id'       => Auth::id(),
                            'admin_name'     => Auth::user()->username,
                            'branch'         => $transfer->to_branch,
                            'change_type'    => 'stock_increase',
                            'previous_stock' => $prevStock,
                            'new_stock'      => $newStock,
                            'description'    => "Recepción de traslado #TR-{$transfer->id} desde {$transfer->from_branch}. Recibido: {$recvQty} uds.",
                        ]);
                    }
                }
            }

            DB::commit();
            return redirect()->back()->with('success', "Traslado recibido exitosamente en {$transfer->to_branch}.");
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function pdf(BranchTransfer $transfer)
    {
        $user = Auth::user();
        if (!$user->isSuperAdmin() && $user->branch && $transfer->from_branch !== $user->branch && $transfer->to_branch !== $user->branch) {
            abort(403, 'No tienes permiso para ver traslados de otra sucursal.');
        }

        $transfer->load(['items.product', 'creator', 'receiver']);
        $options = new \Dompdf\Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'Helvetica');

        $dompdf = new \Dompdf\Dompdf($options);
        $html   = view('reports.branch_transfer_pdf', ['transfer' => $transfer])->render();

        $dompdf->loadHtml($html);
        $dompdf->setPaper('letter', 'landscape');
        $dompdf->render();

        return response($dompdf->output(), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Traslado_#TR-' . str_pad($transfer->id, 4, '0', STR_PAD_LEFT) . '.pdf"',
        ]);
    }

    public function shortagePdf(BranchTransfer $transfer)
    {
        $user = Auth::user();
        if (!$user->isSuperAdmin() && $user->branch && $transfer->from_branch !== $user->branch && $transfer->to_branch !== $user->branch) {
            abort(403, 'No tienes permiso para ver traslados de otra sucursal.');
        }

        $transfer->load(['items.product', 'creator', 'receiver']);

        // Filtrar exclusivamente los productos que hicieron falta
        $shortageItems = $transfer->items->filter(function ($item) {
            return $item->quantity_received !== null && (int)$item->quantity_received < (int)$item->quantity_sent;
        });

        if ($shortageItems->isEmpty()) {
            return redirect()->back()->withErrors([
                'error' => 'Este traslado no presenta productos faltantes ni discrepancias.'
            ]);
        }

        $options = new \Dompdf\Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'Helvetica');

        $dompdf = new \Dompdf\Dompdf($options);
        $html   = view('reports.branch_transfer_shortage_pdf', [
            'transfer'      => $transfer,
            'shortageItems' => $shortageItems,
        ])->render();

        $dompdf->loadHtml($html);
        $dompdf->setPaper('letter', 'portrait');
        $dompdf->render();

        return response($dompdf->output(), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Acta_Faltantes_#TR-' . str_pad($transfer->id, 4, '0', STR_PAD_LEFT) . '.pdf"',
        ]);
    }
}
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DiscardRequest;
use App\Models\InventoryLog;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Dompdf\Dompdf;
use Dompdf\Options;

class DiscardController extends Controller
{
    public function index(Request $request)
    {
        $user         = Auth::user();
        $isSuperAdmin = $user && $user->isSuperAdmin();
        $userBranch   = $user->branch ?? 'Sucursal Managua';

        $status   = $request->get('status', 'all');
        $q        = trim($request->get('q', ''));
        $dateFrom = $request->get('date_from', '');
        $dateTo   = $request->get('date_to', '');

        // Lógica de sucursal:
        // - Superadmin: por defecto filtrado a su propia sucursal ($userBranch), pero puede cambiar o ver 'all'.
        // - Cajero y Admin: estrictamente bloqueados a su sucursal ($userBranch) sin opción a consultar otra.
        if ($isSuperAdmin) {
            $branch = $request->get('branch', $userBranch);
        } else {
            $branch = $userBranch;
        }

        $query = DiscardRequest::with([
            'product:id,name,barcode,sku,price,cost,purchase_tax_percent,tax_percent,stock,image',
            'requester:id,username,name,first_name,last_name,branch',
            'decider:id,username,name,first_name,last_name'
        ]);

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if (!empty($q)) {
            $query->where(function ($sub) use ($q) {
                $sub->whereHas('product', fn($p) => $p->where('name', 'like', "%{$q}%")->orWhere('barcode', 'like', "%{$q}%")->orWhere('sku', 'like', "%{$q}%"))
                    ->orWhereHas('requester', fn($u) => $u->where('username', 'like', "%{$q}%")->orWhere('name', 'like', "%{$q}%"))
                    ->orWhere('reason', 'like', "%{$q}%");
            });
        }

        if (!empty($dateFrom)) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if (!empty($dateTo)) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        if ($branch !== 'all' && !empty($branch)) {
            $query->where('branch', $branch);
        }

        $requests = $query->latest()->paginate(15)->withQueryString();

        $products = Product::where('available', true)
            ->where('branch', $userBranch)
            ->get(['id', 'name', 'stock', 'cost', 'purchase_tax_percent', 'tax_percent', 'price']);

        // Lista de sucursales disponibles en el filtro:
        // Superadmin ve todas las opciones oficiales. Cajero y Admin solo ven su sucursal.
        $branches = $isSuperAdmin ? ['Sucursal Managua', 'Sucursal Leon'] : [$userBranch];

        return Inertia::render('Admin/Discards/Index', [
            'discards'     => $requests,
            'products'     => $products,
            'branches'     => $branches,
            'isSuperAdmin' => $isSuperAdmin,
            'userBranch'   => $userBranch,
            'filters'      => [
                'status'    => $status,
                'q'         => $q,
                'date_from' => $dateFrom,
                'date_to'   => $dateTo,
                'branch'    => $branch,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity'   => 'required|integer|min:1',
            'reason'     => 'required|string|min:3',
        ], [
            'product_id.required' => 'Debes seleccionar un producto.',
            'product_id.exists'   => 'El producto seleccionado no existe.',
            'quantity.required'   => 'Debes ingresar la cantidad a dar de baja.',
            'quantity.integer'    => 'La cantidad debe ser un número entero.',
            'quantity.min'        => 'La cantidad debe ser al menos 1.',
            'reason.required'     => 'Debes especificar el motivo o causa de la baja.',
            'reason.min'          => 'El motivo debe tener al menos :min caracteres.',
        ]);

        $product = Product::findOrFail($validated['product_id']);
        if ($product->stock < $validated['quantity']) {
            return redirect()->back()->withErrors(['quantity' => "El stock disponible es {$product->stock}."]);
        }

        $user = Auth::user();
        $branch = $user->branch ?? 'Sucursal Managua';

        DiscardRequest::create([
            'product_id'   => $validated['product_id'],
            'requested_by' => $user->id,
            'branch'       => $branch,
            'quantity'     => $validated['quantity'],
            'reason'       => $validated['reason'],
            'status'       => 'pending',
        ]);

        return redirect()->back()->with('success', 'Solicitud de baja registrada exitosamente.');
    }

    public function decide(Request $request, DiscardRequest $discard)
    {
        $validated = $request->validate([
            'status'          => 'required|in:approved,rejected',
            'decision_reason' => 'nullable|string',
            'admin_username'  => 'required|string',
            'admin_password'  => 'required|string',
        ]);

        if ($discard->status !== 'pending') {
            return redirect()->back()->withErrors(['status' => 'Esta solicitud ya fue procesada.']);
        }

        // Validar credenciales de autorizaciÃ³n administrativa
        $authorizer = User::where('username', $validated['admin_username'])
            ->whereIn('role', ['admin', 'superadmin'])
            ->first();

        if (!$authorizer || !Hash::check($validated['admin_password'], $authorizer->password)) {
            return redirect()->back()->withErrors([
                'auth' => 'Credenciales de autorizaciÃ³n invÃ¡lidas. Solo un Administrador o Superadmin puede autorizar.',
            ]);
        }

        DB::beginTransaction();
        try {
            $discard->update([
                'status'          => $validated['status'],
                'decision_by'     => $authorizer->id,
                'decision_reason' => $validated['decision_reason'] ?? null,
                'decision_at'     => now(),
            ]);

            // Si se aprueba, descontar stock del producto y registrar log
            if ($validated['status'] === 'approved') {
                $product   = Product::lockForUpdate()->find($discard->product_id);
                $prevStock = $product->stock;
                $newStock  = max(0, $prevStock - $discard->quantity);

                $product->update(['stock' => $newStock]);

                // CÃ¡lculo de costo con IVA
                $baseCost = (float)($product->cost ?? 0);
                $taxPercent = (float)($product->purchase_tax_percent ?? $product->tax_percent ?? 0);
                $costWithTax = $baseCost * (1 + ($taxPercent / 100));
                $totalLoss = (float)$discard->quantity * $costWithTax;

                $authorizerName = trim(($authorizer->first_name ?? '') . ' ' . ($authorizer->last_name ?? ''));
                if (empty($authorizerName)) {
                    $authorizerName = $authorizer->name ?? $authorizer->username;
                }

                InventoryLog::create([
                    'product_id'     => $product->id,
                    'admin_id'       => $authorizer->id,
                    'admin_name'     => $authorizerName,
                    'branch'         => $discard->branch ?? ($authorizer->branch ?? 'Sucursal Managua'),
                    'change_type'    => 'stock_decrease',
                    'previous_stock' => $prevStock,
                    'new_stock'      => $newStock,
                    'description'    => "Salida de inventario por baja (#BAJA-{$discard->id}). Cantidad: -{$discard->quantity}. Costo total: -C$ " . number_format($totalLoss, 2) . ". Motivo: {$discard->reason}. Autorizado por: {$authorizerName}",
                ]);
            }

            DB::commit();

            return redirect()->back()->with([
                'success'             => $validated['status'] === 'approved' 
                    ? 'Baja de inventario autorizada y procesada correctamente.' 
                    : 'Solicitud de baja rechazada.',
                'approved_discard_id' => $validated['status'] === 'approved' ? $discard->id : null,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => 'Error al procesar la baja: ' . $e->getMessage()]);
        }
    }

    public function pdf(DiscardRequest $discard)
    {
        $user = Auth::user();
        if ($user && !$user->isSuperAdmin() && strcasecmp($user->branch ?? '', $discard->branch ?? '') !== 0) {
            abort(403, 'No tienes autorización para acceder a reportes de otra sucursal.');
        }

        $discard->load(['product', 'requester', 'decider']);

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'Helvetica');

        $dompdf = new Dompdf($options);
        $html   = view('reports.discard_pdf', ['discard' => $discard])->render();

        $dompdf->loadHtml($html);
        $dompdf->setPaper('letter', 'portrait');
        $dompdf->render();

        return response($dompdf->output(), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Reporte_Salida_Inventario_#BAJA-' . str_pad($discard->id, 4, '0', STR_PAD_LEFT) . '.pdf"',
        ]);
    }
}


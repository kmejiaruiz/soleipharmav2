<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BodegaAccessRequest;
use App\Models\BodegaMovement;
use App\Models\BodegaStock;
use App\Models\Branch;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class BodegaController extends Controller
{
    public function index(Request $request)
    {
        $q      = trim($request->get('q', ''));
        $bodega = $request->get('bodega', 'all'); // all, debito, merma
        $user   = Auth::user();

        $branches = Branch::orderBy('name')->pluck('name');
        $activeBranch = null;
        $pendingRequest = null;
        $needsBranchSelection = false;
        $accessRequests = null;

        if ($user->branch) {
            $activeBranch = $user->branch;
            // El log de solicitudes a esta sucursal sólo es visible para el admin de la sucursal
            if ($user->role === 'admin') {
                $accessRequests = BodegaAccessRequest::with([
                    'user:id,username,name,first_name,last_name,role',
                    'respondedBy:id,username,name,first_name,last_name'
                ])
                ->where('branch', $user->branch)
                ->latest()
                ->paginate(15)
                ->withQueryString();
            }
        } else {
            // Superadmin (sin sucursal fija asignada)
            $sessionBranch = session('bodega_authorized_branch');
            if ($sessionBranch) {
                $activeBranch = $sessionBranch;
            } else {
                $pendingRequest = BodegaAccessRequest::with('user:id,username,name,first_name,last_name,role')
                    ->where('user_id', $user->id)
                    ->where('status', 'pending')
                    ->latest()
                    ->first();

                if (!$pendingRequest) {
                    $needsBranchSelection = true;
                }
            }
        }

        $products = Product::with('bodegaStock')
            ->when($activeBranch, fn($query) => $query->where(fn($sub) => $sub->where('branch', $activeBranch)->orWhereNull('branch')))
            ->when(!$activeBranch, fn($query) => $query->whereRaw('1 = 0'))
            ->when($q, fn($query) =>
                $query->where('name', 'like', "%{$q}%")
                      ->orWhere('barcode', 'like', "%{$q}%")
            )
            ->paginate(15)
            ->withQueryString()
            ->through(function ($p) {
                $debitoStock = $p->bodegaStock->firstWhere('bodega', 'debito')?->stock ?? 0;
                $mermaStock  = $p->bodegaStock->firstWhere('bodega', 'merma')?->stock ?? 0;
                return [
                    'id'             => $p->id,
                    'name'           => $p->name,
                    'barcode'        => $p->barcode,
                    'image'          => $p->imageUrl(),
                    'branch'         => $p->branch,
                    'sucursal_stock' => $p->stock,
                    'debito_stock'   => $debitoStock,
                    'merma_stock'    => $mermaStock,
                ];
            });

        $recentMovements = BodegaMovement::with(['product:id,name,branch', 'user:id,username'])
            ->when($activeBranch, fn($query) => $query->whereHas('product', fn($p) => $p->where(fn($sub) => $sub->where('branch', $activeBranch)->orWhereNull('branch'))))
            ->when(!$activeBranch, fn($query) => $query->whereRaw('1 = 0'))
            ->latest()
            ->limit(10)
            ->get();

        return Inertia::render('Admin/Bodega/Index', [
            'products'             => $products,
            'recentMovements'      => $recentMovements,
            'filters'              => ['q' => $q, 'bodega' => $bodega],
            'branches'             => $branches,
            'activeBranch'         => $activeBranch,
            'pendingRequest'       => $pendingRequest,
            'needsBranchSelection' => $needsBranchSelection,
            'accessRequests'       => $accessRequests,
        ]);
    }

    public function requestAccess(Request $request)
    {
        $user = Auth::user();
        if ($user->branch) {
            return redirect()->back()->withErrors(['branch' => 'Ya cuenta con una sucursal asignada.']);
        }

        $validated = $request->validate([
            'branch' => 'required|string|exists:branches,name',
        ]);

        // Cancelar cualquier solicitud pendiente previa del mismo usuario
        BodegaAccessRequest::where('user_id', $user->id)
            ->where('status', 'pending')
            ->update(['status' => 'cancelled', 'updated_at' => now()]);

        $accessRequest = BodegaAccessRequest::create([
            'user_id' => $user->id,
            'branch'  => $validated['branch'],
            'status'  => 'pending',
        ]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'request' => $accessRequest]);
        }

        return redirect()->route('bodega.index');
    }

    public function checkRequestStatus(BodegaAccessRequest $accessRequest)
    {
        $user = Auth::user();
        if ($accessRequest->user_id !== $user->id && $user->branch !== $accessRequest->branch) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        if ($accessRequest->status === 'approved' && $accessRequest->user_id === $user->id) {
            session(['bodega_authorized_branch' => $accessRequest->branch]);
        }

        return response()->json([
            'id'     => $accessRequest->id,
            'branch' => $accessRequest->branch,
            'status' => $accessRequest->status,
        ]);
    }

    public function cancelRequest(BodegaAccessRequest $accessRequest)
    {
        $user = Auth::user();
        if ($accessRequest->user_id !== $user->id) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        if ($accessRequest->status === 'pending') {
            $accessRequest->update([
                'status' => 'cancelled',
            ]);
        }

        return redirect()->route('bodega.index');
    }

    public function exitBranch()
    {
        session()->forget('bodega_authorized_branch');
        return redirect()->route('bodega.index');
    }

    public function pendingRequestsForAdmin()
    {
        $user = Auth::user();
        if (!$user || $user->role !== 'admin' || !$user->branch) {
            return response()->json(['pending' => null]);
        }

        $pending = BodegaAccessRequest::with('user:id,username,name,first_name,last_name,role')
            ->where('branch', $user->branch)
            ->where('status', 'pending')
            ->latest()
            ->first();

        return response()->json([
            'pending' => $pending ? [
                'id'         => $pending->id,
                'branch'     => $pending->branch,
                'user'       => [
                    'id'       => $pending->user->id,
                    'username' => $pending->user->username,
                    'name'     => $pending->user->full_name ?? ($pending->user->name ?: $pending->user->username),
                    'role'     => $pending->user->role,
                ],
                'created_at' => $pending->created_at->format('d/m/Y H:i:s'),
            ] : null,
        ]);
    }

    public function respondToRequest(Request $request, BodegaAccessRequest $accessRequest)
    {
        $user = Auth::user();
        if (!$user || $user->role !== 'admin' || strcasecmp(trim($user->branch ?? ''), trim($accessRequest->branch)) !== 0) {
            return response()->json(['error' => 'No tiene permisos para autorizar esta solicitud.'], 403);
        }

        $validated = $request->validate([
            'decision' => 'required|in:approved,rejected',
        ]);

        $accessRequest->update([
            'status'          => $validated['decision'],
            'responded_by_id' => $user->id,
            'responded_at'    => now(),
        ]);

        return response()->json([
            'success' => true,
            'status'  => $accessRequest->status,
        ]);
    }

    public function transfer(Request $request)
    {
        $validated = $request->validate([
            'product_id'  => 'required|exists:products,id',
            'from_bodega' => 'required|in:sucursal,debito,merma',
            'to_bodega'   => 'required|in:sucursal,debito,merma|different:from_bodega',
            'quantity'    => 'required|integer|min:1',
            'reason'      => 'nullable|string',
        ]);

        $user = Auth::user();
        $targetBranch = $user->branch ?? session('bodega_authorized_branch');
        if (!$targetBranch) {
            return redirect()->back()->withErrors(['product_id' => 'No tiene una sucursal autorizada para realizar traslados.']);
        }

        DB::beginTransaction();
        try {
            $product  = Product::lockForUpdate()->find($validated['product_id']);

            if ($product->branch && strcasecmp(trim($targetBranch), trim($product->branch)) !== 0) {
                DB::rollBack();
                return redirect()->back()->withErrors(['product_id' => "No tiene permisos para mover productos pertenecientes a {$product->branch}."]);
            }

            $qty  = $validated['quantity'];
            $from = $validated['from_bodega'];
            $to   = $validated['to_bodega'];

            // Validar stock de origen
            if ($from === 'sucursal') {
                if ($product->stock < $qty) {
                    DB::rollBack();
                    return redirect()->back()->withErrors(['quantity' => "Stock insuficiente en sucursal. Disponible: {$product->stock}"]);
                }
                $product->decrement('stock', $qty);
            } else {
                $fromStock = BodegaStock::where('product_id', $product->id)->where('bodega', $from)->first();
                if (!$fromStock || $fromStock->stock < $qty) {
                    DB::rollBack();
                    $avail = $fromStock?->stock ?? 0;
                    return redirect()->back()->withErrors(['quantity' => "Stock insuficiente en bodega {$from}. Disponible: {$avail}"]);
                }
                $fromStock->decrement('stock', $qty);
            }

            // Sumar a destino
            if ($to === 'sucursal') {
                $product->increment('stock', $qty);
            } else {
                $toStock = BodegaStock::firstOrCreate(
                    ['product_id' => $product->id, 'bodega' => $to],
                    ['stock' => 0]
                );
                $toStock->increment('stock', $qty);
            }

            // Log movimiento
            BodegaMovement::create([
                'product_id'  => $product->id,
                'from_bodega' => $from,
                'to_bodega'   => $to,
                'quantity'    => $qty,
                'reason'      => $validated['reason'] ?? null,
                'user_id'     => Auth::id(),
            ]);

            DB::commit();
            return redirect()->back()->with('success', 'Traslado de bodega realizado exitosamente.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
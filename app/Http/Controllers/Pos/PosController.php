<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\CashSession;
use App\Models\InventoryLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PosController extends Controller
{
    // ── Página principal POS ───────────────────────────────────────────────────
    public function index()
    {
        $user = Auth::user();

        $openSession = CashSession::with('opener:id,username')
            ->where('status', 'open')
            ->when($user->branch, fn($q) => $q->where('branch', $user->branch))
            ->latest('opened_at')
            ->first();

        return Inertia::render('Admin/Cash/Pos', [
            'openSession' => $openSession,
        ]);
    }

    // ── Búsqueda de productos (AJAX) ───────────────────────────────────────────
    public function search(Request $request)
    {
        $q      = trim($request->get('q', ''));
        $branch = Auth::user()->branch;

        if (strlen($q) < 1) {
            return response()->json([]);
        }

        $products = Product::query()
            ->where('available', true)
            ->where('stock', '>', 0)
            ->when($branch, fn($query) => $query->where(fn($q2) =>
                $q2->where('branch', $branch)->orWhereNull('branch')
            ))
            ->where(fn($query) =>
                $query->where('name', 'like', "%{$q}%")
                      ->orWhere('barcode', $q)
                      ->orWhere('id', (int) $q)
            )
            ->orderByRaw("CASE WHEN barcode = ? THEN 0 ELSE 1 END", [$q])
            ->limit(12)
            ->get(['id', 'name', 'price', 'stock', 'image', 'barcode']);

        return response()->json($products->map(fn($p) => [
            'id'    => $p->id,
            'name'  => $p->name,
            'price' => (float) $p->price,
            'stock' => (int) $p->stock,
            'image' => $p->imageUrl(),
            'sku'   => $p->barcode,
        ]));
    }

    // ── Procesar venta ─────────────────────────────────────────────────────────
    public function sale(Request $request)
    {
        $validated = $request->validate([
            'items'          => 'required|array|min:1',
            'items.*.id'     => 'required|integer|exists:products,id',
            'items.*.qty'    => 'required|integer|min:1',
            'items.*.price'  => 'required|numeric|min:0',
            'pay_method'     => 'required|in:efectivo,tarjeta,transferencia',
            'amount_paid'    => 'nullable|numeric|min:0',
            'discount_type'  => 'nullable|in:fixed,percent',
            'discount_value' => 'nullable|numeric|min:0',
            'client_name'    => 'nullable|string|max:255',
        ]);

        $user        = Auth::user();
        $openSession = CashSession::with('opener:id,username')
            ->where('status', 'open')
            ->when($user->branch, fn($q) => $q->where('branch', $user->branch))
            ->latest('opened_at')
            ->first();

        if (!$openSession) {
            return response()->json([
                'success' => false,
                'message' => 'No hay una sesión de caja abierta en esta sucursal. Debe abrir caja antes de facturar.',
            ], 422);
        }

        if ($user->role === 'cajero' && (int)($openSession->opened_by ?? $openSession->user_id) !== (int)$user->id) {
            $opener = $openSession->opener?->username ?? 'otro usuario';
            return response()->json([
                'success' => false,
                'message' => "Se ha detectado una sesión de caja abierta por el usuario '{$opener}'. No puede facturar en una caja ajena.",
            ], 403);
        }

        DB::beginTransaction();
        try {
            // ── Calcular totales ───────────────────────────────────────────────
            $subtotal = 0;
            $itemsToInsert = [];

            foreach ($validated['items'] as $item) {
                $product = Product::lockForUpdate()->find($item['id']);

                if ($product->stock < $item['qty']) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => "Stock insuficiente para: {$product->name}. Disponible: {$product->stock}",
                    ], 422);
                }

                $officialPrice = (float) $product->price;
                $lineTotal     = $officialPrice * $item['qty'];
                $subtotal     += $lineTotal;
                $itemsToInsert[] = [
                    'product'  => $product,
                    'qty'      => $item['qty'],
                    'price'    => $officialPrice,
                    'subtotal' => $lineTotal,
                ];
            }

            // ── Descuento ──────────────────────────────────────────────────────
            $discType  = $validated['discount_type']  ?? 'fixed';
            $discValue = (float) ($validated['discount_value'] ?? 0);
            $discAmt   = 0;

            if ($discValue > 0) {
                if ($discType === 'percent') {
                    $discAmt = $subtotal * (min($discValue, 100) / 100);
                } else {
                    $discAmt = min($discValue, $subtotal);
                }
            }

            $total     = round($subtotal - $discAmt, 2);
            $amtPaid   = (float) ($validated['amount_paid'] ?? $total);
            $change    = max(0, round($amtPaid - $total, 2));

            if ($validated['pay_method'] === 'efectivo' && $amtPaid < $total - 0.001) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'El monto recibido es menor al total a cobrar.',
                ], 422);
            }

            // ── Crear Order ────────────────────────────────────────────────────
            $order = Order::create([
                'user_id'        => $user->id,
                'total'          => $total,
                'status'         => 'completado',
                'payment_method' => $validated['pay_method'],
                'amount_paid'    => $amtPaid,
                'change_given'   => $change,
                'branch'         => $user->branch,
                'cash_session_id'=> $openSession?->id,
                'client_name'    => $validated['client_name'] ?? 'Consumidor Final',
            ]);

            // ── Insertar items, descontar stock y registrar en historial ───────
            foreach ($itemsToInsert as $row) {
                OrderItem::create([
                    'order_id'   => $order->id,
                    'product_id' => $row['product']->id,
                    'quantity'   => $row['qty'],
                    'price'      => $row['price'],
                ]);

                $prevStock = (int) $row['product']->stock;
                $newStock  = $prevStock - (int) $row['qty'];
                $row['product']->decrement('stock', $row['qty']);

                InventoryLog::create([
                    'product_id'     => $row['product']->id,
                    'admin_id'       => $user->id,
                    'admin_name'     => $user->username,
                    'branch'         => $row['product']->branch ?: ($user->branch ?: 'Sucursal Managua'),
                    'change_type'    => 'stock_decrease',
                    'previous_stock' => $prevStock,
                    'new_stock'      => $newStock,
                    'description'    => "Venta en POS (Orden #{$order->id}). Vendido: {$row['qty']} uds.",
                ]);
            }

            DB::commit();

            return response()->json([
                'success'     => true,
                'order_id'    => $order->id,
                'subtotal'    => $subtotal,
                'discount'    => $discAmt,
                'total'       => $total,
                'change'      => $change,
                'receipt_url' => route('pos.receipt', $order->id),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Error interno: ' . $e->getMessage()], 500);
        }
    }

    // ── Recibo de venta (vista imprimible térmica) ─────────────────────────────────
    public function receipt(Order $order)
    {
        $order->load(['items.product', 'user:id,username,name,branch']);
        return view('pos.receipt', compact('order'));
    }

    // ── Historial de ventas (reimpresiones) ────────────────────────────────────
    public function salesHistory(Request $request)
    {
        $user       = Auth::user();
        $sessionId  = $request->get('session_id');
        $dateFrom   = $request->get('date_from', now()->toDateString());
        $dateTo     = $request->get('date_to',   now()->toDateString());
        $searchUser = $request->get('search_user');

        $query = Order::with('user:id,username')
            ->where('status', 'completado')
            ->when($sessionId, fn($q) => $q->where('cash_session_id', $sessionId))
            ->when(!$sessionId, fn($q) => $q
                ->whereDate('created_at', '>=', $dateFrom)
                ->whereDate('created_at', '<=', $dateTo)
            )
            ->when($searchUser, fn($q) =>
                $q->whereHas('user', fn($u) => $u->where('username', 'like', "%{$searchUser}%"))
            )
            ->when($user->branch, fn($q) => $q->where('branch', $user->branch))
            ->latest()
            ->limit(100);

        return response()->json($query->get()->map(fn($o) => [
            'id'           => $o->id,
            'created_at'   => $o->created_at?->format('Y-m-d H:i'),
            'client_name'  => $o->client_name ?: 'Consumidor Final',
            'cashier_name' => $o->user?->username ?? '—',
            'total'        => $o->total,
            'pay_method'   => $o->payment_method,
        ]));
    }

    public function receiptPdf(Order $order)
    {
        $order->load(['items.product', 'user']);
        $options = new \Dompdf\Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'Courier');

        $dompdf = new \Dompdf\Dompdf($options);
        $html   = view('reports.pos_receipt_ticket_pdf', ['order' => $order])->render();

        $dompdf->loadHtml($html);
        $itemCount = $order->items->count();
        $height = max(380, 260 + ($itemCount * 22));
        $dompdf->setPaper([0, 0, 226.77, $height], 'portrait');
        $dompdf->render();

        return response($dompdf->output(), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Ticket_Venta_#FAC-' . str_pad($order->id, 6, '0', STR_PAD_LEFT) . '.pdf"',
        ]);
    }
}
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DebitNote;
use App\Models\GoodsEntry;
use App\Models\GoodsEntryItem;
use App\Models\InventoryLog;
use App\Models\Product;
use App\Models\ProductOrder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class GoodsEntryController extends Controller
{
    /**
     * Tasa de IVA predeterminada (configurable globalmente).
     * Futuro: obtener de tabla de configuración del sistema.
     */
    private float $defaultIvaRate = 0.15;

    // ── Mostrar pantalla de Entrada de Mercadería ─────────────────────────────
    public function show(ProductOrder $order)
    {
        $order->load(['items.product', 'supplier', 'admin:id,username']);

        return Inertia::render('Admin/ProductOrders/GoodsEntry', [
            'order'   => $order,
            'ivaRate' => $this->defaultIvaRate,
        ]);
    }

    // ── Procesar y aplicar Entrada de Mercadería con lógica de notas de débito ─
    public function store(Request $request, ProductOrder $order)
    {
        // Aceptar pedidos en estado 'pending' o 'applied'
        // (apply() del ProductOrderController marca como 'applied' antes de la entrada física)
        if (!in_array($order->status, ['pending', 'applied'])) {
            return redirect()->route('product-orders.index')
                ->withErrors(['status' => 'Este pedido ya fue procesado con una entrada de mercadería.']);
        }

        $validated = $request->validate([
            'admin_username'           => 'required|string',
            'admin_password'           => 'required|string',
            'invoice_number'           => 'nullable|string|max:255',
            'invoice_subtotal'         => 'nullable|numeric|min:0',
            'invoice_tax'              => 'nullable|numeric|min:0',
            'iva_rate'                 => 'nullable|numeric|min:0|max:1',
            'notes'                    => 'nullable|string',
            'items'                    => 'required|array|min:1',
            'items.*.product_id'       => 'required|exists:products,id',
            'items.*.expected_qty'     => 'required|integer|min:0',
            'items.*.scanned_qty'      => 'required|integer|min:0',
            'items.*.factured_qty'     => 'nullable|integer|min:0',
            'items.*.unit_cost'        => 'nullable|numeric|min:0',
            'items.*.missing_reason'   => 'nullable|string',
        ]);

        // Verificar credenciales del usuario Administrador o Superadmin autorizante
        $adminUser = User::where('username', $validated['admin_username'])->first();
        if (!$adminUser || !Hash::check($validated['admin_password'], $adminUser->password) || !in_array($adminUser->role, ['admin', 'superadmin'])) {
            return redirect()->back()->withErrors([
                'admin_credentials' => 'Las credenciales de usuario o contraseña de administrador son incorrectas o no posee rol de Administrador.',
            ]);
        }

        $ivaRate = (float) ($validated['iva_rate'] ?? $this->defaultIvaRate);

        DB::beginTransaction();
        try {
            // ── 1. Calcular totales del SISTEMA ───────────────────────────────
            $systemSubtotal = 0.0;
            foreach ($validated['items'] as $item) {
                $qty      = (int)   $item['scanned_qty'];
                $unitCost = (float) ($item['unit_cost'] ?? 0);
                $systemSubtotal += $qty * $unitCost;
            }
            $systemTax   = round($systemSubtotal * $ivaRate, 2);
            $systemTotal = round($systemSubtotal + $systemTax, 2);

            // ── 2. Totales de la FACTURA FÍSICA ───────────────────────────────
            $invoiceSubtotal = round((float) ($validated['invoice_subtotal'] ?? 0), 2);
            $invoiceTax      = round((float) ($validated['invoice_tax']      ?? 0), 2);
            $invoiceTotal    = round($invoiceSubtotal + $invoiceTax, 2);

            // ── 3. Crear registro de Entrada de Mercadería ────────────────────
            $entry = GoodsEntry::create([
                'order_id'         => $order->id,
                'supplier_id'      => $order->supplier_id,
                'invoice_number'   => $validated['invoice_number']   ?? null,
                'invoice_subtotal' => $invoiceSubtotal,
                'invoice_tax'      => $invoiceTax,
                'system_subtotal'  => round($systemSubtotal, 2),
                'system_tax'       => $systemTax,
                'system_total'     => $systemTotal,
                'iva_rate'         => $ivaRate,
                'notes'            => $validated['notes'] ?? null,
                'created_by'       => $adminUser->id,  // quien autorizó la entrada
                'received_by'      => Auth::id(),         // quien está logueado y recibe físicamente
                'branch'           => $order->branch ?? (Auth::user()->branch ?? 'Sucursal Managua'),
            ]);

            // ── 4. Procesar ítems, actualizar stock y detectar excesos ─────────
            $qtyExcessDetails = [];   // Para nota de débito por cantidades
            $qtyExtraCost     = 0.0;  // Suma de costos extra por exceso de cant.

            foreach ($validated['items'] as $item) {
                $productId   = (int)   $item['product_id'];
                $expectedQty = (int)   $item['expected_qty'];
                $scannedQty  = (int)   $item['scanned_qty'];
                $factoredQty = (int)  ($item['factured_qty'] ?? $scannedQty);
                $unitCost    = (float) ($item['unit_cost']   ?? 0);
                $missingReason = $item['missing_reason'] ?? null;

                // Guardar ítem usando goods_entry_id (nombre real de la columna FK en la BD)
                GoodsEntryItem::create([
                    'goods_entry_id'    => $entry->id,
                    'product_id'        => $productId,
                    'quantity_ordered'  => $expectedQty,
                    'quantity_received' => $scannedQty,
                    'quantity_factured' => $factoredQty,
                    'unit_cost'         => $unitCost,
                    'item_iva_rate'     => $ivaRate,
                    'missing_reason'    => $missingReason,
                ]);

                // Acreditar stock si la cantidad recibida es > 0
                if ($scannedQty > 0) {
                    $product   = Product::lockForUpdate()->find($productId);
                    $prevStock = $product->stock;
                    $newStock  = $prevStock + $scannedQty;
                    $newCost   = $unitCost > 0
                        ? round($unitCost * (1 + $ivaRate), 2)   // costo + IVA para precio de costo del producto
                        : $product->cost;

                    $product->update([
                        'stock' => $newStock,
                        'cost'  => $newCost,
                    ]);

                    InventoryLog::create([
                        'product_id'     => $product->id,
                        'admin_id'       => Auth::id(),
                        'admin_name'     => Auth::user()->username,
                        'branch'         => $product->branch ?: ($entry->branch ?: ($order->branch ?: Auth::user()->branch)),
                        'change_type'    => 'supplier_entry',
                        'previous_stock' => $prevStock,
                        'new_stock'      => $newStock,
                        'description'    => "Entrada de mercadería (Boleta #{$order->id}, Factura: {$entry->invoice_number}). Recibido: {$scannedQty} uds. Costo c/IVA: C$ {$newCost}.",
                    ]);
                }

                // ── Detectar EXCESO de cantidad ───────────────────────────────
                if ($scannedQty > $expectedQty) {
                    $excessQty       = $scannedQty - $expectedQty;
                    $extraCostNoIva  = $excessQty * $unitCost;
                    $extraCostConIva = round($extraCostNoIva * (1 + $ivaRate), 2);
                    $qtyExtraCost   += $extraCostConIva;

                    $qtyExcessDetails[] = [
                        'product_id'      => $productId,
                        'product_name'    => $product->name ?? "Producto #{$productId}",
                        'ordered_qty'     => $expectedQty,
                        'received_qty'    => $scannedQty,
                        'excess_qty'      => $excessQty,
                        'unit_cost'       => $unitCost,
                        'extra_cost'      => $extraCostNoIva,
                        'extra_cost_iva'  => $extraCostConIva,
                        'iva_rate'        => $ivaRate,
                    ];
                }
            }

            // ── 5. Lógica de Notas de Débito ──────────────────────────────────
            $hasCostDiff = $invoiceTotal > $systemTotal;   // Factura > Sistema
            $hasQtyExcess = count($qtyExcessDetails) > 0;  // Hay exceso en cantidades

            if ($hasCostDiff || $hasQtyExcess) {
                $costDifference = $hasCostDiff ? round($invoiceTotal - $systemTotal, 2) : 0.0;
                $qtyExtraCost   = round($qtyExtraCost, 2);

                // Determinar tipo de nota
                $noteType = match(true) {
                    $hasCostDiff && $hasQtyExcess => 'both',
                    $hasQtyExcess                 => 'quantities',
                    default                       => 'costs',
                };

                DebitNote::create([
                    'goods_entry_id' => $entry->id,
                    'order_id'       => $order->id,
                    'supplier_id'    => $order->supplier_id,
                    'type'           => $noteType,
                    'cost_difference'=> $costDifference,
                    'qty_extra_cost' => $qtyExtraCost,
                    'total_amount'   => round($costDifference + $qtyExtraCost, 2),
                    'details'        => $hasQtyExcess ? $qtyExcessDetails : null,
                    'total_debit'    => round($costDifference + $qtyExtraCost, 2),
                    'note_number'    => 'ND-' . str_pad($entry->id, 6, '0', STR_PAD_LEFT),
                    'created_by'     => $adminUser->id,
                ]);
            }

            // ── 6. Marcar el pedido como 'received' (recibido) ────────────────
            $order->update([
                'status'     => 'received',
                'applied_by' => $adminUser->id,
                'applied_at' => now(),
            ]);

            DB::commit();

            $noteMsg = ($hasCostDiff || $hasQtyExcess)
                ? ' Se generó una Nota de Débito automáticamente.'
                : '';

            return redirect()->route('product-orders.index', ['tab' => 'entries'])
                ->with('success', "Entrada aplicada exitosamente (Boleta #{$order->id}).{$noteMsg} El stock ha sido actualizado.")
                ->with('new_entry_id', $entry->id);

        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()
                ->withErrors(['error' => 'Error al procesar la entrada: ' . $e->getMessage()]);
        }
    }

    // ── Descargar / Ver PDF Estilo Crystal Reports (Dompdf) ──────────────────
    public function pdf(GoodsEntry $entry)
    {
        $entry->load(['order', 'supplier', 'creator', 'items.product', 'debitNote']);

        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('defaultFont', 'Helvetica');

        $dompdf = new \Dompdf\Dompdf($options);
        $html   = view('reports.goods_entry_pdf', ['entry' => $entry])->render();

        $dompdf->loadHtml($html);
        $dompdf->setPaper('letter', 'landscape');
        $dompdf->render();

        return response($dompdf->output(), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Boleta_Entrada_#' . ($entry->order_id ?? $entry->id) . '.pdf"',
        ]);
    }

    // ── Descargar / Ver PDF Nota de Débito (Dompdf) ─────────────────────────
    public function debitNotePdf(DebitNote $debitNote)
    {
        $debitNote->load(['goodsEntry', 'order', 'supplier', 'creator']);

        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('defaultFont', 'Helvetica');

        $dompdf = new \Dompdf\Dompdf($options);
        $html   = view('reports.debit_note_pdf', ['debitNote' => $debitNote])->render();

        $dompdf->loadHtml($html);
        $dompdf->setPaper('letter', 'portrait');
        $dompdf->render();

        return response($dompdf->output(), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Nota_Debito_#ND-' . str_pad($debitNote->id, 6, '0', STR_PAD_LEFT) . '.pdf"',
        ]);
    }
}

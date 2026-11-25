<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryCount;
use App\Models\InventoryCountItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InventoryCountController extends Controller
{
    // ── Guardar Preliminar de Conteo (Borrador) ───────────────────────────────
    public function saveDraftCount(Request $request)
    {
        $validated = $request->validate([
            'branch'      => 'required|string',
            'notes'       => 'nullable|string',
            'count_id'    => 'nullable|integer',
            'category_id' => 'nullable',
            'items'       => 'required|array',
            'items.*.product_id'    => 'required|integer',
            'items.*.system_stock'  => 'required|integer',
            'items.*.counted_stock' => 'required|integer',
            'items.*.notes'         => 'nullable|string',
        ]);

        $branch     = $validated['branch'];
        $notes      = $validated['notes'] ?? null;
        $categoryId = (!empty($validated['category_id']) && $validated['category_id'] !== 'all') ? (int) $validated['category_id'] : null;

        // Validar regla de negocio: los artículos previamente contados en esta sucursal solo pueden volver a contarse tras 10 ventas
        $shortBranch = str_replace('Sucursal ', '', $branch);
        foreach ($validated['items'] as $it) {
            $pId = (int) $it['product_id'];
            $lastCounted = DB::table('inventory_count_items')
                ->join('inventory_counts', 'inventory_counts.id', '=', 'inventory_count_items.inventory_count_id')
                ->where('inventory_counts.status', 'aplicado')
                ->where('inventory_count_items.product_id', $pId)
                ->where(function ($q) use ($branch, $shortBranch) {
                    $q->where('inventory_counts.branch', $branch)
                      ->orWhere('inventory_counts.branch', $shortBranch);
                })
                ->max(DB::raw('COALESCE(inventory_counts.applied_at, inventory_counts.created_at)'));

            if ($lastCounted) {
                $salesCount = DB::table('order_items')
                    ->join('orders', 'orders.id', '=', 'order_items.order_id')
                    ->where('order_items.product_id', $pId)
                    ->where('orders.status', 'completado')
                    ->where(function ($q) use ($branch, $shortBranch) {
                        $q->where('orders.branch', $branch)
                          ->orWhere('orders.branch', $shortBranch);
                    })
                    ->where('orders.created_at', '>', $lastCounted)
                    ->count();

                if ($salesCount < 10) {
                    $pModel = Product::find($pId);
                    $pName = $pModel ? $pModel->name : "ID #$pId";
                    return response()->json([
                        'success' => false,
                        'message' => "El producto '{$pName}' no puede ser vuelto a contar aún. Requiere al menos 10 ventas desde su último conteo aplicado en {$branch} (lleva {$salesCount}/10 ventas).",
                    ], 422);
                }
            }
        }

        DB::beginTransaction();
        try {
            if (!empty($validated['count_id'])) {
                $count = InventoryCount::find($validated['count_id']);
            } else {
                $count = InventoryCount::where('branch', $branch)
                    ->where('status', 'preliminar')
                    ->latest()
                    ->first();
            }

            if (!$count) {
                // Generar número correlativo
                $todayYearMonth = date('Ym');
                $nextSeq = (InventoryCount::whereYear('created_at', date('Y'))->count()) + 1;
                $countNumber = 'CNT-' . $todayYearMonth . '-' . str_pad($nextSeq, 3, '0', STR_PAD_LEFT);

                $count = new InventoryCount();
                $count->count_number = $countNumber;
                $count->branch       = $branch;
                $count->created_by   = Auth::id() ?? 1;
                $count->status       = 'preliminar';
            }

            $count->category_id = $categoryId;
            $count->notes       = $notes;
            $count->save();

            // Sincronizar items
            $totalCountedUnits = 0;
            $totalDiscrepancies = 0;
            $netCostDiff = 0.00;

            foreach ($validated['items'] as $it) {
                $product = Product::find($it['product_id']);
                if (!$product) continue;

                $sysStock = (int) $it['system_stock'];
                $cntStock = (int) $it['counted_stock'];
                $diff     = $cntStock - $sysStock;
                $unitCost = (float) $product->cost;
                $costDiff = round($diff * $unitCost, 2);

                if ($diff !== 0) {
                    $totalDiscrepancies++;
                }
                $totalCountedUnits += $cntStock;
                $netCostDiff       += $costDiff;

                InventoryCountItem::updateOrCreate(
                    [
                        'inventory_count_id' => $count->id,
                        'product_id'         => $product->id,
                    ],
                    [
                        'system_stock'    => $sysStock,
                        'counted_stock'   => $cntStock,
                        'difference'      => $diff,
                        'unit_cost'       => $unitCost,
                        'total_cost_diff' => $costDiff,
                        'notes'           => $it['notes'] ?? null,
                    ]
                );
            }

            $count->total_items_counted = count($validated['items']);
            $count->total_discrepancies = $totalDiscrepancies;
            $count->net_cost_difference = $netCostDiff;
            $count->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Preliminar de conteo {$count->count_number} guardado correctamente.",
                'count'   => $count->load('items'),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar preliminar: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ── Aplicar Conteo Directamente en la Bodega de la Sucursal ────────────────
    public function applyCount(Request $request, $id)
    {
        $count = InventoryCount::with('items.product')->findOrFail($id);

        if ($count->status === 'aplicado') {
            return response()->json([
                'success' => false,
                'message' => 'Este conteo ya fue aplicado anteriormente.',
            ], 422);
        }

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

        $normalize = function ($str) {
            $s = strtolower(trim($str ?? ''));
            $s = str_replace(['á', 'é', 'í', 'ó', 'ú'], ['a', 'e', 'i', 'o', 'u'], $s);
            return $s;
        };

        $sessionUser   = Auth::user();
        $countBranch   = $normalize($count->branch);
        $sessionBranch = $sessionUser ? $normalize($sessionUser->branch) : '';
        $authBranch    = $normalize($authorizingUser->branch);

        // Validaciones de sucursal
        if ($sessionUser && !$sessionUser->isSuperAdmin()) {
            $sessionMatchesCount = (!empty($sessionBranch) && !empty($countBranch)) && (
                ($sessionBranch === $countBranch) ||
                str_contains($sessionBranch, $countBranch) ||
                str_contains($countBranch, $sessionBranch)
            );

            if (!$sessionMatchesCount) {
                return response()->json([
                    'success' => false,
                    'message' => 'El usuario en sesión no tiene permisos en esta sucursal (' . $count->branch . ').',
                ], 422);
            }
        }

        if (!$authorizingUser->isSuperAdmin()) {
            $authMatchesCount = (!empty($authBranch) && !empty($countBranch)) && (
                ($authBranch === $countBranch) ||
                str_contains($authBranch, $countBranch) ||
                str_contains($countBranch, $authBranch)
            );

            if (!$authMatchesCount) {
                return response()->json([
                    'success' => false,
                    'message' => 'Este usuario no tiene permisos en esta sucursal (' . $count->branch . ').',
                ], 422);
            }

            if (!empty($sessionBranch) && !empty($authBranch)) {
                $authMatchesSession = ($authBranch === $sessionBranch) ||
                    str_contains($authBranch, $sessionBranch) ||
                    str_contains($sessionBranch, $authBranch);

                if (!$authMatchesSession) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Este usuario no tiene permisos en esta sucursal.',
                    ], 422);
                }
            }
        }

        DB::beginTransaction();
        try {
            $adminId   = $authorizingUser->id;
            $adminName = $authorizingUser->username;

            foreach ($count->items as $item) {
                $product = $item->product;
                if (!$product) continue;

                $prevStock = $item->system_stock;
                $newStock  = $item->counted_stock;
                $diff      = $item->difference;

                DB::table('branch_product_stock')->updateOrInsert(
                    ['product_id' => $product->id, 'branch' => $count->branch],
                    [
                        'stock'      => $newStock,
                        'updated_at' => now(),
                    ]
                );

                if ($diff !== 0) {
                    DB::table('inventory_log')->insert([
                        'product_id'     => $product->id,
                        'admin_id'       => $adminId,
                        'admin_name'     => $adminName,
                        'branch'         => $count->branch ?: ($product->branch ?: 'Sucursal Managua'),
                        'change_type'    => $diff > 0 ? 'stock_increase' : 'stock_decrease',
                        'previous_stock' => $prevStock,
                        'new_stock'      => $newStock,
                        'description'    => "Ajuste por Conteo Físico {$count->count_number} en {$count->branch}: " . ($diff > 0 ? "+{$diff}" : "{$diff}") . " uds. (Costo diff: C$ {$item->total_cost_diff})",
                        'created_at'     => now(),
                    ]);
                }
            }

            $count->status     = 'aplicado';
            $count->applied_by = $adminId;
            $count->applied_at = now();
            $count->save();

            DB::commit();

            return response()->json([
                'success'              => true,
                'message'              => "Conteo {$count->count_number} aplicado con éxito en la bodega de {$count->branch}.",
                'has_discrepancies'    => $count->total_discrepancies > 0,
                'discrepancies_count'  => $count->total_discrepancies,
                'discrepancies_pdf_url'=> route('reports.inventory.count.discrepanciesPdf', $count->id),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al aplicar el conteo en bodega: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ── Generar Reporte de Discrepancias / Faltantes y Sobrantes (PDF) ─────────
    public function discrepanciesPdf($id)
    {
        $count = InventoryCount::with(['items.product.category', 'creator', 'appliedBy', 'category'])
            ->findOrFail($id);

        $discrepancies = $count->items->filter(fn($it) => (int)$it->difference !== 0)->values();

        $faltantesUnits = 0;
        $faltantesCost  = 0.00;
        $sobrantesUnits = 0;
        $sobrantesCost  = 0.00;
        $totalSystem    = 0;
        $totalCounted   = 0;

        foreach ($discrepancies as $it) {
            $diff = (int) $it->difference;
            $costDiff = (float) $it->total_cost_diff;
            $totalSystem  += (int) $it->system_stock;
            $totalCounted += (int) $it->counted_stock;

            if ($diff < 0) {
                $faltantesUnits += abs($diff);
                $faltantesCost  += $costDiff;
            } else {
                $sobrantesUnits += $diff;
                $sobrantesCost  += $costDiff;
            }
        }

        $kpis = [
            'faltantes_units' => $faltantesUnits,
            'faltantes_cost'  => $faltantesCost,
            'sobrantes_units' => $sobrantesUnits,
            'sobrantes_cost'  => $sobrantesCost,
            'net_cost'        => (float) $count->net_cost_difference,
            'total_system'    => $totalSystem,
            'total_counted'   => $totalCounted,
        ];

        $options = new \Dompdf\Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'Helvetica');

        $dompdf = new \Dompdf\Dompdf($options);
        $html   = view('reports.inventory_discrepancies_pdf', [
            'count'         => $count,
            'discrepancies' => $discrepancies,
            'kpis'          => $kpis,
        ])->render();

        $dompdf->loadHtml($html);
        $dompdf->setPaper('letter', 'landscape');
        $dompdf->render();

        $filename = 'Acta_Discrepancias_' . $count->count_number . '_' . date('Y-m-d') . '.pdf';

        return response($dompdf->output(), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }
}

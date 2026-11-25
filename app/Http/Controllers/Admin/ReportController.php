<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\InventoryLog;
use App\Models\Product;
use App\Models\User;
use App\Models\Supplier;
use App\Models\Category;
use App\Models\InventoryCount;
use App\Models\InventoryCountItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class ReportController extends Controller
{
    // ── Reporte de Ventas ──────────────────────────────────────────────────────
    public function sales(Request $request)
    {
        $dateFrom = $request->get('date_from', now()->startOfMonth()->toDateString());
        $dateTo   = $request->get('date_to',   now()->toDateString());
        $branch   = $request->get('branch', Auth::user()->branch);

        $query = Order::with(['user:id,username', 'items.product:id,name'])
            ->where('status', 'completado')
            ->whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo)
            ->when($branch, fn($q) => $q->where('branch', $branch));

        $totalRevenue     = (float) $query->sum('total');
        $totalOrders      = $query->count();
        $avgTicket        = $totalOrders > 0 ? round($totalRevenue / $totalOrders, 2) : 0;

        $sales = (clone $query)
            ->latest()
            ->paginate(20)
            ->withQueryString()
            ->through(fn($o) => [
                'id'             => $o->id,
                'created_at'     => $o->created_at?->format('d/m/Y H:i'),
                'cashier'        => $o->user?->username ?? 'Consumidor Final',
                'payment_method' => $o->payment_method,
                'total'          => (float) $o->total,
                'amount_paid'    => (float) $o->amount_paid,
                'change_given'   => (float) $o->change_given,
                'items_count'    => $o->items->sum('quantity'),
                'receipt_url'    => route('pos.receipt', $o->id),
            ]);

        return Inertia::render('Admin/Reports/Sales', [
            'sales'      => $sales,
            'summary'    => [
                'totalRevenue' => $totalRevenue,
                'totalOrders'  => $totalOrders,
                'avgTicket'    => $avgTicket,
            ],
            'filters'    => [
                'date_from' => $dateFrom,
                'date_to'   => $dateTo,
                'branch'    => $branch,
            ],
        ]);
    }

    // ── Historial de Movimientos de Inventario ───────────────────────────────
    public function inventoryMovements(Request $request)
    {
        $changeType = $request->get('change_type', 'all');
        $q          = trim($request->get('q', ''));

        $movements = InventoryLog::with('product:id,name,image')
            ->when($changeType !== 'all', fn($query) => $query->where('change_type', $changeType))
            ->when($q, fn($query) =>
                $query->whereHas('product', fn($p) => $p->where('name', 'like', "%{$q}%"))
                      ->orWhere('admin_name', 'like', "%{$q}%")
                      ->orWhere('description', 'like', "%{$q}%")
            )
            ->orderBy('id', 'desc')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Inventory/Movements', [
            'movements'  => $movements,
            'filters'    => ['change_type' => $changeType, 'q' => $q],
        ]);
    }

    // ── Reporte de Bodega / Inventario ──────────────────────────────────────────
    public function inventory(Request $request)
    {
        $q          = trim($request->get('q', ''));
        $supplierId = $request->get('supplier_id', 'all');
        $categoryId = $request->get('category_id', 'all');
        $authUser   = Auth::user();
        $defaultBranch = !empty($authUser?->branch) ? $authUser->branch : 'all';
        $branch     = $request->get('branch', $defaultBranch);
        $showCost   = $request->boolean('show_cost', true);
        $countId    = $request->get('count_id');
        $eligibility = $request->get('eligibility', 'all');

        $shortBranch = ($branch && $branch !== 'all') ? str_replace('Sucursal ', '', $branch) : null;

        // Consulta de productos con soporte para existencia por sucursal (branch_product_stock)
        $query = Product::with(['suppliers:id,name', 'category:id,name,code,location'])
            ->when($branch && $branch !== 'all', function ($q) use ($branch, $shortBranch) {
                $productCols = [
                    'products.id', 'products.sku', 'products.barcode', 'products.name', 'products.description',
                    'products.sale_price', 'products.price', 'products.image', 'products.category_id',
                    'products.supplier_id', 'products.cost', 'products.purchase_tax_percent', 'products.utility_percent',
                    'products.tax_percent', 'products.created_at', 'products.updated_at', 'products.available', 'products.reason_unavailable'
                ];
                $q->leftJoin('branch_product_stock', function ($join) use ($branch) {
                    $join->on('branch_product_stock.product_id', '=', 'products.id')
                         ->where('branch_product_stock.branch', '=', $branch);
                })
                ->select(array_merge($productCols, [
                    DB::raw('COALESCE(branch_product_stock.stock, 0) as stock'),
                    DB::raw("'$branch' as branch")
                ]))
                ->selectRaw("
                    (
                        SELECT MAX(COALESCE(c.applied_at, c.created_at))
                        FROM inventory_count_items ici
                        JOIN inventory_counts c ON c.id = ici.inventory_count_id
                        WHERE ici.product_id = products.id
                          AND c.status = 'aplicado'
                          AND (c.branch = ? OR c.branch = ?)
                    ) as last_counted_at
                ", [$branch, $shortBranch])
                ->selectRaw("
                    (
                        SELECT COUNT(DISTINCT o.id)
                        FROM order_items oi
                        JOIN orders o ON o.id = oi.order_id
                        WHERE oi.product_id = products.id
                          AND o.status = 'completado'
                          AND (o.branch = ? OR o.branch = ?)
                          AND o.created_at > (
                              SELECT COALESCE(MAX(COALESCE(c2.applied_at, c2.created_at)), '1970-01-01')
                              FROM inventory_count_items ici2
                              JOIN inventory_counts c2 ON c2.id = ici2.inventory_count_id
                              WHERE ici2.product_id = products.id
                                AND c2.status = 'aplicado'
                                AND (c2.branch = ? OR c2.branch = ?)
                          )
                    ) as sales_since_last_count
                ", [$branch, $shortBranch, $branch, $shortBranch]);
            }, function ($q) {
                $q->select('products.*')
                  ->selectRaw('NULL as last_counted_at')
                  ->selectRaw('0 as sales_since_last_count');
            })
            ->when($q, function ($query, $q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('products.name', 'like', "%{$q}%")
                        ->orWhere('products.barcode', 'like', "%{$q}%")
                        ->orWhere('products.sku', 'like', "%{$q}%");
                });
            })
            ->when($supplierId && $supplierId !== 'all', function ($q) use ($supplierId) {
                $q->where(function ($sub) use ($supplierId) {
                    $sub->where('products.supplier_id', $supplierId)
                        ->orWhereHas('suppliers', fn($sq) => $sq->where('suppliers.id', $supplierId));
                });
            })
            ->when($categoryId && $categoryId !== 'all', function ($q) use ($categoryId) {
                $q->where('products.category_id', $categoryId);
            });

        // Totales globales para KPIs
        if ($branch && $branch !== 'all') {
            $stats = DB::table('products')
                ->leftJoin('branch_product_stock', function ($join) use ($branch) {
                    $join->on('branch_product_stock.product_id', '=', 'products.id')
                         ->where('branch_product_stock.branch', '=', $branch);
                })
                ->when($q, function ($query, $q) {
                    $query->where(function ($sub) use ($q) {
                        $sub->where('products.name', 'like', "%{$q}%")
                            ->orWhere('products.barcode', 'like', "%{$q}%")
                            ->orWhere('products.sku', 'like', "%{$q}%");
                    });
                })
                ->when($supplierId && $supplierId !== 'all', function ($q) use ($supplierId) {
                    $q->where(function ($sub) use ($supplierId) {
                        $sub->where('products.supplier_id', $supplierId)
                            ->orWhereExists(function ($ex) use ($supplierId) {
                                $ex->select(DB::raw(1))
                                   ->from('supplier_catalog')
                                   ->whereColumn('supplier_catalog.product_id', 'products.id')
                                   ->where('supplier_catalog.supplier_id', $supplierId);
                            });
                    });
                })
                ->when($categoryId && $categoryId !== 'all', function ($q) use ($categoryId) {
                    $q->where('products.category_id', $categoryId);
                })
                ->selectRaw('
                    COUNT(products.id) as total_products,
                    COALESCE(SUM(COALESCE(branch_product_stock.stock, 0)), 0) as total_units,
                    COALESCE(SUM(COALESCE(branch_product_stock.stock, 0) * products.cost), 0) as total_cost_val,
                    COALESCE(SUM(COALESCE(branch_product_stock.stock, 0) * products.price), 0) as total_sale_val,
                    SUM(CASE WHEN COALESCE(branch_product_stock.stock, 0) > 0 AND COALESCE(branch_product_stock.stock, 0) <= 5 THEN 1 ELSE 0 END) as low_stock_count,
                    SUM(CASE WHEN COALESCE(branch_product_stock.stock, 0) <= 0 THEN 1 ELSE 0 END) as out_of_stock_count
                ')
                ->first();
        } else {
            $stats = DB::table('products')
                ->when($q, function ($query, $q) {
                    $query->where(function ($sub) use ($q) {
                        $sub->where('products.name', 'like', "%{$q}%")
                            ->orWhere('products.barcode', 'like', "%{$q}%")
                            ->orWhere('products.sku', 'like', "%{$q}%");
                    });
                })
                ->when($supplierId && $supplierId !== 'all', function ($q) use ($supplierId) {
                    $q->where(function ($sub) use ($supplierId) {
                        $sub->where('products.supplier_id', $supplierId)
                            ->orWhereExists(function ($ex) use ($supplierId) {
                                $ex->select(DB::raw(1))
                                   ->from('supplier_catalog')
                                   ->whereColumn('supplier_catalog.product_id', 'products.id')
                                   ->where('supplier_catalog.supplier_id', $supplierId);
                            });
                    });
                })
                ->when($categoryId && $categoryId !== 'all', function ($q) use ($categoryId) {
                    $q->where('products.category_id', $categoryId);
                })
                ->selectRaw('
                    COUNT(products.id) as total_products,
                    COALESCE(SUM(products.stock), 0) as total_units,
                    COALESCE(SUM(products.stock * products.cost), 0) as total_cost_val,
                    COALESCE(SUM(products.stock * products.price), 0) as total_sale_val,
                    SUM(CASE WHEN products.stock > 0 AND products.stock <= 5 THEN 1 ELSE 0 END) as low_stock_count,
                    SUM(CASE WHEN products.stock <= 0 THEN 1 ELSE 0 END) as out_of_stock_count
                ')
                ->first();
        }

        if ($branch && $branch !== 'all') {
            if ($eligibility === 'eligible') {
                $query->havingRaw('(last_counted_at IS NULL OR sales_since_last_count >= 10)');
            } elseif ($eligibility === 'locked') {
                $query->havingRaw('(last_counted_at IS NOT NULL AND sales_since_last_count < 10)');
            }
        }

        $products = (clone $query)
            ->orderBy('products.name', 'asc')
            ->paginate(50)
            ->withQueryString()
            ->through(function ($p) {
                $isCounted = !is_null($p->last_counted_at);
                $sales = (int) ($p->sales_since_last_count ?? 0);
                $p->can_count = !$isCounted || ($sales >= 10);
                $p->sales_needed = $isCounted ? max(0, 10 - $sales) : 0;
                $p->sales_since_last_count = $isCounted ? $sales : null;
                return $p;
            });

        $suppliers  = Supplier::orderBy('name')->get(['id', 'name']);
        $categories = Category::orderBy('name')->get(['id', 'name', 'code', 'location']);
        $branches   = !empty($authUser?->branch)
            ? collect([$authUser->branch])
            : DB::table('branch_product_stock')->distinct()->pluck('branch')->filter()->values();

        // Conteo Preliminar Activo / Guardado para la sucursal actual
        $activeCount = null;
        if ($countId) {
            $activeCount = InventoryCount::with(['items.product'])->find($countId);
        } elseif ($branch && $branch !== 'all') {
            $activeCount = InventoryCount::with(['items.product'])
                ->where('branch', $branch)
                ->where('status', 'preliminar')
                ->latest()
                ->first();
        }

        $countsQuery = InventoryCount::with('items')->orderBy('id', 'desc');
        if ($branch && $branch !== 'all') {
            $countsQuery->where('branch', $branch);
        }
        $recentCounts = $countsQuery->limit(30)->get();

        return Inertia::render('Admin/Reports/Inventory', [
            'products'     => $products,
            'suppliers'    => $suppliers,
            'categories'   => $categories,
            'branches'     => $branches,
            'activeCount'  => $activeCount,
            'recentCounts' => $recentCounts,
            'summary'      => [
                'totalProducts'   => (int) ($stats->total_products ?? 0),
                'totalUnits'      => (int) ($stats->total_units ?? 0),
                'totalCostVal'    => round((float) ($stats->total_cost_val ?? 0), 2),
                'totalSaleVal'    => round((float) ($stats->total_sale_val ?? 0), 2),
                'lowStockCount'   => (int) ($stats->low_stock_count ?? 0),
                'outOfStockCount' => (int) ($stats->out_of_stock_count ?? 0),
            ],
            'filters'      => [
                'q'           => $q,
                'supplier_id' => $supplierId,
                'category_id' => $categoryId,
                'branch'      => $branch,
                'show_cost'   => $showCost,
                'count_id'    => $countId,
                'eligibility' => $eligibility,
            ],
        ]);
    }

    // ── Exportar Hoja de Conteo / Inventario en PDF ─────────────────────────────
    public function inventoryPdf(Request $request)
    {
        $supplierId  = $request->get('supplier_id');
        $categoryId  = $request->get('category_id');
        $authUser    = Auth::user();
        $branch      = $request->get('branch', $authUser?->branch ?: null);
        $showCost    = $request->boolean('show_cost', true);
        $eligibility = $request->get('eligibility', 'eligible'); // Por defecto en toma física, listar los elegibles para conteo

        $supplier = ($supplierId && $supplierId !== 'all') ? Supplier::find($supplierId) : null;
        $category = ($categoryId && $categoryId !== 'all') ? Category::find($categoryId) : null;
        $shortBranch = ($branch && $branch !== 'all') ? str_replace('Sucursal ', '', $branch) : null;

        $query = Product::with(['suppliers:id,name', 'category:id,name,code,location'])
            ->when($branch && $branch !== 'all', function ($q) use ($branch, $shortBranch) {
                $productCols = [
                    'products.id', 'products.sku', 'products.barcode', 'products.name', 'products.description',
                    'products.sale_price', 'products.price', 'products.image', 'products.category_id',
                    'products.supplier_id', 'products.cost', 'products.purchase_tax_percent', 'products.utility_percent',
                    'products.tax_percent', 'products.created_at', 'products.updated_at', 'products.available', 'products.reason_unavailable'
                ];
                $q->leftJoin('branch_product_stock', function ($join) use ($branch) {
                    $join->on('branch_product_stock.product_id', '=', 'products.id')
                         ->where('branch_product_stock.branch', '=', $branch);
                })
                ->select(array_merge($productCols, [
                    DB::raw('COALESCE(branch_product_stock.stock, 0) as stock'),
                    DB::raw("'$branch' as branch")
                ]))
                ->selectRaw("
                    (
                        SELECT MAX(COALESCE(c.applied_at, c.created_at))
                        FROM inventory_count_items ici
                        JOIN inventory_counts c ON c.id = ici.inventory_count_id
                        WHERE ici.product_id = products.id
                          AND c.status = 'aplicado'
                          AND (c.branch = ? OR c.branch = ?)
                    ) as last_counted_at
                ", [$branch, $shortBranch])
                ->selectRaw("
                    (
                        SELECT COUNT(DISTINCT o.id)
                        FROM order_items oi
                        JOIN orders o ON o.id = oi.order_id
                        WHERE oi.product_id = products.id
                          AND o.status = 'completado'
                          AND (o.branch = ? OR o.branch = ?)
                          AND o.created_at > (
                              SELECT COALESCE(MAX(COALESCE(c2.applied_at, c2.created_at)), '1970-01-01')
                              FROM inventory_count_items ici2
                              JOIN inventory_counts c2 ON c2.id = ici2.inventory_count_id
                              WHERE ici2.product_id = products.id
                                AND c2.status = 'aplicado'
                                AND (c2.branch = ? OR c2.branch = ?)
                          )
                    ) as sales_since_last_count
                ", [$branch, $shortBranch, $branch, $shortBranch]);
            }, function ($q) {
                $q->select('products.*')
                  ->selectRaw('NULL as last_counted_at')
                  ->selectRaw('0 as sales_since_last_count');
            })
            ->when($supplierId && $supplierId !== 'all', function ($q) use ($supplierId) {
                $q->where(function ($sub) use ($supplierId) {
                    $sub->where('products.supplier_id', $supplierId)
                        ->orWhereHas('suppliers', fn($sq) => $sq->where('suppliers.id', $supplierId));
                });
            })
            ->when($categoryId && $categoryId !== 'all', function ($q) use ($categoryId) {
                $q->where('products.category_id', $categoryId);
            });

        if ($branch && $branch !== 'all') {
            if ($eligibility === 'eligible') {
                $query->havingRaw('(last_counted_at IS NULL OR sales_since_last_count >= 10)');
            } elseif ($eligibility === 'locked') {
                $query->havingRaw('(last_counted_at IS NOT NULL AND sales_since_last_count < 10)');
            }
        }

        $products = $query->orderBy('products.name')->get()->map(function ($p) {
            $isCounted = !is_null($p->last_counted_at);
            $sales = (int) ($p->sales_since_last_count ?? 0);
            $p->can_count = !$isCounted || ($sales >= 10);
            $p->sales_needed = $isCounted ? max(0, 10 - $sales) : 0;
            $p->sales_since_last_count = $isCounted ? $sales : null;
            return $p;
        });

        $options = new \Dompdf\Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'Helvetica');

        $dompdf = new \Dompdf\Dompdf($options);
        $html   = view('reports.inventory_pdf', [
            'products'    => $products,
            'supplier'    => $supplier,
            'category'    => $category,
            'branch'      => $branch,
            'showCost'    => $showCost,
            'eligibility' => $eligibility,
        ])->render();

        $dompdf->loadHtml($html);
        $dompdf->setPaper('letter', 'landscape');
        $dompdf->render();

        $filename = 'Conteo_Ciclico_Bodega_' . ($branch ? str_replace(' ', '_', $branch) . '_' : '') . date('Y-m-d') . '.pdf';

        return response($dompdf->output(), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }

    }

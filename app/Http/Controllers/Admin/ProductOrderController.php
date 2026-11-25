<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductOrder;
use App\Models\ProductOrderItem;
use App\Models\GoodsEntry;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\InventoryLog;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Dompdf\Dompdf;
use Dompdf\Options;

class ProductOrderController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->get('status', 'all');
        $tab    = $request->get('tab', 'orders'); // orders o entries
        $user   = Auth::user();
        $branch = $user->branch;

        $orders = ProductOrder::with(['items.product', 'supplier', 'admin:id,username,name,first_name,last_name', 'appliedBy:id,username,name,first_name,last_name', 'goodsEntry.creator:id,username,name,first_name,last_name'])
            ->when($status !== 'all', fn($q) => $q->where('status', $status))
            ->when($branch, fn($q) => $q->where('branch', $branch))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $goodsEntries = GoodsEntry::with(['order', 'supplier', 'creator:id,username,name,first_name,last_name', 'items.product', 'debitNote'])
            ->when($branch, fn($q) => $q->whereHas('order', fn($o) => $o->where('branch', $branch)))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $suppliers = Supplier::all(['id', 'name']);
        $products  = Product::where('available', true)
            ->when($branch, fn($q) => $q->where(fn($sub) => $sub->where('branch', $branch)->orWhereNull('branch')))
            ->with('suppliers:id')
            ->get(['id', 'name', 'barcode', 'sku', 'price', 'cost', 'stock', 'supplier_id', 'branch']);

        $branches = \App\Models\Branch::orderBy('name')->pluck('name');

        return Inertia::render('Admin/ProductOrders/Index', [
            'orders'       => $orders,
            'goodsEntries' => $goodsEntries,
            'suppliers'    => $suppliers,
            'branches'     => $branches,
            'products'     => $products->map(fn($p) => [
                'id'           => $p->id,
                'name'         => $p->name,
                'barcode'      => $p->barcode ?? $p->sku,
                'price'        => (float) $p->price,
                'cost'         => (float) $p->cost,
                'stock'        => (int) $p->stock,
                'branch'       => $p->branch,
                'supplier_id'  => $p->supplier_id,
                'supplier_ids' => $p->suppliers->pluck('id')->toArray(),
            ]),
            'filters'      => ['status' => $status, 'tab' => $tab],
        ]);
    }

    public function store(Request $request)
    {
        // Limpiar formato de precios si vienen con coma en vez de punto
        $items = $request->input('items', []);
        if (is_array($items)) {
            foreach ($items as $k => $item) {
                if (isset($item['price'])) {
                    $items[$k]['price'] = (float) str_replace(',', '.', (string) $item['price']);
                }
            }
            $request->merge(['items' => $items]);
        }

        $validated = $request->validate([
            'admin_username'     => 'required|string',
            'admin_password'     => 'required|string',
            'supplier_id'        => 'required|exists:suppliers,id',
            'branch'             => 'nullable|string',
            'items'              => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity'   => 'required|integer|min:1',
            'items.*.price'      => 'nullable|numeric|min:0',
        ], [
            'admin_username.required'     => 'El usuario autorizante es obligatorio.',
            'admin_password.required'     => 'La contraseña de autorización es obligatoria.',
            'supplier_id.required'        => 'Debe seleccionar un proveedor.',
            'items.required'              => 'Debe agregar al menos un producto.',
            'items.min'                   => 'Debe agregar al menos un producto al pedido.',
            'items.*.product_id.required' => 'Debe seleccionar un producto válido en cada fila.',
            'items.*.quantity.required'   => 'Debe especificar la cantidad.',
            'items.*.quantity.min'        => 'La cantidad mínima es 1.',
        ]);

        // Validar credenciales del usuario autorizante
        $adminUser = User::where('username', $validated['admin_username'])->first();
        if (!$adminUser || !Hash::check($validated['admin_password'], $adminUser->password)) {
            return redirect()->back()->withErrors([
                'admin_credentials' => 'Las credenciales del usuario autorizante son incorrectas.',
            ]);
        }

        DB::beginTransaction();
        try {
            $total = 0;
            foreach ($validated['items'] as $item) {
                $unitCost = (float)($item['price'] ?? 0);
                $total += ((int)$item['quantity'] * $unitCost);
            }

            $order = ProductOrder::create([
                'admin_id'    => $adminUser->id,
                'admin_name'  => $adminUser->username,
                'branch'      => $validated['branch'] ?? (Auth::user()->branch ?? ($adminUser->branch ?? 'Sucursal Managua')),
                'supplier_id' => $validated['supplier_id'],
                'status'      => 'pending',
                'order_date'  => now(),
                'total'       => $total,
            ]);

            foreach ($validated['items'] as $item) {
                ProductOrderItem::create([
                    'order_id'   => $order->id,
                    'product_id' => $item['product_id'],
                    'quantity'   => $item['quantity'],
                    'price'      => $item['price'] ?? 0,
                ]);

                Product::where('id', $item['product_id'])
                    ->whereNull('supplier_id')
                    ->update(['supplier_id' => $validated['supplier_id']]);
            }

            DB::commit();
            return redirect()->route('product-orders.index')
                ->with('success', "Pedido #{$order->id} creado y autorizado exitosamente.")
                ->with('new_order_id', $order->id);
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => 'Error al crear el pedido: ' . $e->getMessage()]);
        }
    }

    public function pdf(ProductOrder $order)
    {
        $user = Auth::user();
        if ($user->branch && $order->branch && strcasecmp(trim($user->branch), trim($order->branch)) !== 0) {
            abort(403, 'No tiene permisos para ver pedidos de otra sucursal.');
        }

        $order->load(['items.product', 'supplier', 'admin', 'appliedBy', 'goodsEntry.creator']);

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'Helvetica');

        $dompdf = new Dompdf($options);
        $html = view('reports.product_order_pdf', ['order' => $order])->render();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('letter', 'landscape');
        $dompdf->render();

        return response($dompdf->output(), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="boleta_pedido_' . $order->id . '.pdf"',
        ]);
    }

    public function apply(Request $request, ProductOrder $order)
    {
        $user = Auth::user();
        if ($user->branch && $order->branch && strcasecmp(trim($user->branch), trim($order->branch)) !== 0) {
            return redirect()->back()->withErrors(['branch' => "No tiene permisos para aplicar pedidos de otra sucursal."]);
        }

        if ($order->status !== 'pending') {
            return redirect()->back()->withErrors(['status' => 'Este pedido ya fue aplicado.']);
        }

        $validated = $request->validate([
            'admin_username' => 'required|string',
            'admin_password' => 'required|string',
        ]);

        $adminUser = User::where('username', $validated['admin_username'])->first();
        if (!$adminUser || !Hash::check($validated['admin_password'], $adminUser->password) || !in_array($adminUser->role, ['admin', 'superadmin'])) {
            return redirect()->back()->withErrors([
                'admin_credentials' => 'Las credenciales de usuario o contraseña de administrador son incorrectas o no posee rol de Administrador.',
            ]);
        }

        DB::beginTransaction();
        try {
            $order->load('items.product');

            foreach ($order->items as $item) {
                $product   = Product::lockForUpdate()->find($item->product_id);
                $prevStock = $product->stock;
                $newStock  = $prevStock + $item->quantity;

                $product->update(['stock' => $newStock]);

                InventoryLog::create([
                    'product_id'     => $product->id,
                    'admin_id'       => $adminUser->id,
                    'admin_name'     => $adminUser->username,
                    'branch'         => $product->branch ?: ($order->branch ?: $adminUser->branch),
                    'change_type'    => 'supplier_entry',
                    'previous_stock' => $prevStock,
                    'new_stock'      => $newStock,
                    'description'    => "Entrada de mercancía aplicada (#{$order->id}). Cantidad recibida: {$item->quantity}",
                ]);
            }

            $order->update([
                'status'     => 'applied',
                'applied_by' => $adminUser->id,
                'applied_at' => now(),
            ]);

            DB::commit();
            return redirect()->back()->with('success', 'Pedido aplicado exitosamente. El stock de los productos ha sido actualizado.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => 'Error al aplicar el pedido: ' . $e->getMessage()]);
        }
    }
}

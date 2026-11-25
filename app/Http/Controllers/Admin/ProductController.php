<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\InventoryLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ProductController extends Controller
{
    // ── Listado de productos ───────────────────────────────────────────────────
    public function index(Request $request)
    {
        $q          = trim($request->get('q', ''));
        $status     = $request->get('status', 'all'); // all, available, unavailable, low_stock
        $branch     = $request->get('branch', Auth::user()->branch);
        $supplierId = $request->get('supplier_id');

        $products = Product::query()
            ->when($q, fn($query) =>
                $query->where('name', 'like', "%{$q}%")
                      ->orWhere('barcode', 'like', "%{$q}%")
                      ->orWhere('id', (int) $q)
            )
            ->when($branch, fn($query) => $query->where('branch', $branch))
            ->when($supplierId, fn($query) => $query->whereHas('suppliers', fn($sub) =>
                $sub->where('suppliers.id', $supplierId)
            ))
            ->when($status === 'available',   fn($query) => $query->where('available', true))
            ->when($status === 'unavailable', fn($query) => $query->where('available', false))
            ->when($status === 'low_stock',   fn($query) => $query->where('stock', '<=', 5))
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString()
            ->through(fn($p) => [
                'id'                 => $p->id,
                'name'               => $p->name,
                'description'        => $p->description,
                'price'                => (float) $p->price,
                'cost'                 => (float) $p->cost,
                'purchase_tax_percent' => (float) ($p->purchase_tax_percent ?? 15),
                'utility_percent'      => (float) $p->utility_percent,
                'tax_percent'          => (float) $p->tax_percent,
                'stock'                => (int) $p->stock,
                'available'            => (bool) $p->available,
                'reason_unavailable'   => $p->reason_unavailable,
                'barcode'              => $p->barcode,
                'branch'               => $p->branch,
                'image_url'            => $p->imageUrl(),
                'created_at'           => $p->created_at?->format('d/m/Y H:i'),
            ]);

        $suppliers = \App\Models\Supplier::select('id', 'name')->orderBy('name')->get();

        return Inertia::render('Admin/Products/Index', [
            'products'   => $products,
            'suppliers'  => $suppliers,
            'filters'    => [
                'q'           => $q,
                'status'      => $status,
                'branch'      => $branch,
                'supplier_id' => $supplierId,
            ],
        ]);
    }

    // ── Crear nuevo producto ───────────────────────────────────────────────────
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'                 => 'required|string|max:255',
            'description'          => 'nullable|string',
            'price'                => 'required|numeric|min:0',
            'cost'                 => 'nullable|numeric|min:0',
            'purchase_tax_percent' => 'nullable|numeric|min:0|max:100',
            'utility_percent'      => 'nullable|numeric|min:0|max:500',
            'tax_percent'          => 'nullable|numeric|min:0|max:100',
            'stock'                => 'required|integer|min:0',
            'barcode'              => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('products')->where(fn($q) => $q->where('branch', $request->input('branch', Auth::user()->branch))),
            ],
            'branch'               => 'nullable|string|max:255',
            'available'            => 'boolean',
            'reason_unavailable'   => 'nullable|string',
            'image'                => 'nullable|image|max:2048', // 2MB max
            'image_url'            => 'nullable|string|url',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('products', 'public');
            $imagePath = basename($imagePath);
        } elseif (!empty($validated['image_url'])) {
            $imagePath = $validated['image_url'];
        }

        $product = Product::create([
            'name'                 => $validated['name'],
            'description'          => $validated['description'] ?? null,
            'price'                => $validated['price'],
            'cost'                 => $validated['cost'] ?? 0,
            'purchase_tax_percent' => $validated['purchase_tax_percent'] ?? 15,
            'utility_percent'      => $validated['utility_percent'] ?? 30,
            'tax_percent'          => $validated['tax_percent'] ?? 15,
            'stock'                => 0, // Stock inicial siempre en 0 (ingresa vía Entrada de Mercadería)
            'barcode'              => $validated['barcode'] ?? null,
            'branch'               => $validated['branch'] ?? Auth::user()->branch,
            'available'            => $validated['available'] ?? true,
            'reason_unavailable'   => $validated['reason_unavailable'] ?? null,
            'image'                => $imagePath,
        ]);

        // Log inicial de stock
        InventoryLog::create([
            'product_id'     => $product->id,
            'admin_id'       => Auth::id(),
            'admin_name'     => Auth::user()->username,
            'branch'         => $product->branch,
            'change_type'    => 'edit',
            'previous_stock' => 0,
            'new_stock'      => 0,
            'description'    => 'Producto creado exitosamente con stock 0.',
        ]);

        return redirect()->back()->with('success', 'Producto creado exitosamente (Stock inicial: 0).');
    }

    // ── Actualizar producto ────────────────────────────────────────────────────
    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name'                 => 'required|string|max:255',
            'description'          => 'nullable|string',
            'price'                => 'required|numeric|min:0',
            'cost'                 => 'nullable|numeric|min:0',
            'purchase_tax_percent' => 'nullable|numeric|min:0|max:100',
            'utility_percent'      => 'nullable|numeric|min:0|max:500',
            'tax_percent'          => 'nullable|numeric|min:0|max:100',
            'barcode'              => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('products')->where(fn($q) => $q->where('branch', $request->input('branch', $product->branch)))->ignore($product->id),
            ],
            'branch'               => 'nullable|string|max:255',
            'available'          => 'boolean',
            'reason_unavailable' => 'nullable|string',
            'image'              => 'nullable|image|max:2048',
            'image_url'          => 'nullable|string|url',
        ]);

        // El stock no se puede alterar manualmente en la edición del producto
        $prevStock = (int) $product->stock;

        $validated['stock'] = $prevStock;

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('products', 'public');
            $validated['image'] = basename($imagePath);
        } elseif (!empty($validated['image_url'])) {
            $validated['image'] = $validated['image_url'];
        }

        $product->update($validated);

        // Registrar cambio de stock si se modificó
        if ($prevStock !== (int) $validated['stock']) {
            $diff = (int) $validated['stock'] - $prevStock;
            $type = $diff > 0 ? 'stock_increase' : 'stock_decrease';
            InventoryLog::create([
                'product_id'     => $product->id,
                'admin_id'       => Auth::id(),
                'admin_name'     => Auth::user()->username,
                'change_type'    => $type,
                'previous_stock' => $prevStock,
                'new_stock'      => $product->stock,
                'description'    => "Stock actualizado manualmente de {$prevStock} a {$product->stock}.",
            ]);
        }

        return redirect()->back()->with('success', 'Producto actualizado exitosamente.');
    }

    // ── Toggle rápido de disponibilidad ───────────────────────────────────────
    public function toggleAvailability(Request $request, Product $product)
    {
        $validated = $request->validate([
            'available'          => 'required|boolean',
            'reason_unavailable' => 'nullable|string',
        ]);

        $product->update([
            'available'          => $validated['available'],
            'reason_unavailable' => $validated['available'] ? null : ($validated['reason_unavailable'] ?? 'Desactivado por administrador'),
        ]);

        return redirect()->back()->with('success', 'Estado de disponibilidad actualizado.');
    }

    // ── Eliminar producto ──────────────────────────────────────────────────────
    public function destroy(Product $product)
    {
        $product->delete();
        return redirect()->back()->with('success', 'Producto eliminado exitosamente.');
    }
}

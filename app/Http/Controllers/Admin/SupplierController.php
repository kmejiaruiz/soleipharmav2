<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $q = trim($request->get('q', ''));

        $userBranch = Auth::user()->branch ?: 'Sucursal Managua';

        $suppliers = Supplier::query()
            ->when($q, fn($query) =>
                $query->where('name', 'like', "%{$q}%")
                      ->orWhere('contact_name', 'like', "%{$q}%")
                      ->orWhere('phone', 'like', "%{$q}%")
            )
            ->withCount(['products' => fn($q) => $q->where('products.branch', $userBranch)])
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/Suppliers/Index', [
            'suppliers' => $suppliers,
            'filters'   => ['q' => $q],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'         => 'required|string|max:255',
            'contact_name' => 'nullable|string|max:255',
            'phone'        => 'nullable|string|max:50',
            'email'        => 'nullable|email|max:255',
            'address'      => 'nullable|string',
            'notes'        => 'nullable|string',
        ]);

        Supplier::create($validated);

        return redirect()->back()->with('success', 'Proveedor registrado exitosamente.');
    }

    public function update(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'name'         => 'required|string|max:255',
            'contact_name' => 'nullable|string|max:255',
            'phone'        => 'nullable|string|max:50',
            'email'        => 'nullable|email|max:255',
            'address'      => 'nullable|string',
            'notes'        => 'nullable|string',
        ]);

        $supplier->update($validated);

        return redirect()->back()->with('success', 'Proveedor actualizado exitosamente.');
    }

    public function destroy(Supplier $supplier)
    {
        $supplier->delete();
        return redirect()->back()->with('success', 'Proveedor eliminado.');
    }

    // ── Catálogo de Productos por Proveedor ────────────────────────────────────
    /**
     * Muestra el formulario de vinculación de productos con este proveedor.
     * Devuelve el proveedor con sus productos ya vinculados (pivot cost_price)
     * y la lista completa de productos disponibles para vincular.
     */
    public function catalog(Supplier $supplier)
    {
        $userBranch = Auth::user()->branch ?: 'Sucursal Managua';

        // Solo productos de la sucursal del usuario para evitar duplicidad visual en el catálogo
        $supplier->load(['products' => fn($q) => $q->where('products.branch', $userBranch)->select('products.id', 'products.name', 'products.barcode', 'products.cost', 'products.purchase_tax_percent', 'products.utility_percent', 'products.tax_percent', 'products.price')]);

        $allProducts = Product::where('available', true)
            ->where('branch', $userBranch)
            ->orderBy('name')
            ->get(['id', 'name', 'barcode', 'cost', 'purchase_tax_percent', 'utility_percent', 'tax_percent', 'price']);

        return Inertia::render('Admin/Suppliers/Catalog', [
            'supplier'    => $supplier,
            'allProducts' => $allProducts,
        ]);
    }

    /**
     * Sincroniza los productos vinculados al proveedor en todas las sucursales.
     */
    public function syncCatalog(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'product_ids'   => 'nullable|array',
            'product_ids.*' => 'exists:products,id',
        ]);

        $selectedIds = $validated['product_ids'] ?? [];

        // Obtener nombres y códigos de barra de los productos seleccionados
        $selectedProducts = Product::whereIn('id', $selectedIds)->get(['id', 'name', 'barcode']);
        $names = $selectedProducts->pluck('name')->filter()->unique();
        $barcodes = $selectedProducts->pluck('barcode')->filter()->unique();

        // Encontrar los IDs de estos productos en TODAS las sucursales para mantener la vinculación uniforme
        $allBranchProductIds = Product::where(function ($q) use ($names, $barcodes) {
            $q->whereIn('name', $names);
            if ($barcodes->isNotEmpty()) {
                $q->orWhereIn('barcode', $barcodes);
            }
        })->pluck('id')->toArray();

        // Sincronizar catálogo para todas las sucursales
        $supplier->products()->sync($allBranchProductIds);

        // Actualizar supplier_id en los productos vinculados
        Product::whereIn('id', $allBranchProductIds)->update(['supplier_id' => $supplier->id]);

        // Limpiar supplier_id de los que ya no están vinculados a este proveedor
        Product::where('supplier_id', $supplier->id)
            ->whereNotIn('id', $allBranchProductIds)
            ->update(['supplier_id' => null]);

        return redirect()->back()->with('success', 'Catálogo de productos actualizado exitosamente en todas las sucursales.');
    }

    /**
     * Actualiza costos, IVA de compra, IVA de venta y precios de venta en todas las sucursales.
     */
    public function updateCosts(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'items'                        => 'required|array',
            'items.*.id'                   => 'required|exists:products,id',
            'items.*.cost'                 => 'required|numeric|min:0',
            'items.*.purchase_tax_percent' => 'nullable|numeric|min:0|max:100',
            'items.*.utility_percent'      => 'nullable|numeric|min:0|max:500',
            'items.*.tax_percent'          => 'required|numeric|min:0|max:100',
            'items.*.price'                => 'required|numeric|min:0',
        ]);

        foreach ($validated['items'] as $item) {
            $p = Product::find($item['id']);
            if (!$p) continue;

            Product::where('name', $p->name)
                ->when(!empty($p->barcode), fn($q) => $q->orWhere('barcode', $p->barcode))
                ->update([
                    'cost'                 => $item['cost'],
                    'purchase_tax_percent' => $item['purchase_tax_percent'] ?? 15.00,
                    'utility_percent'      => $item['utility_percent'] ?? 30.00,
                    'tax_percent'          => $item['tax_percent'],
                    'price'                => $item['price'],
                ]);
        }

        return redirect()->back()->with('success', 'Costos e IVA de artículos actualizados exitosamente en todas las sucursales.');
    }
}


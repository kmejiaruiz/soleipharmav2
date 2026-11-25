<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class BranchController extends Controller
{
    public function index()
    {
        $this->authorizeSuperAdmin();

        $branches = Branch::orderBy('id')->get()->map(function ($b) {
            $b->product_count = DB::table('products')->where('branch', $b->name)->count();
            $b->user_count    = DB::table('users')->where('branch', $b->name)->count();
            return $b;
        });

        return Inertia::render('Admin/Branches/Index', compact('branches'));
    }

    public function store(Request $request)
    {
        $this->authorizeSuperAdmin();

        $data = $request->validate([
            'name'    => 'required|string|max:100|unique:branches,name',
            'address' => 'nullable|string|max:255',
            'phone'   => 'nullable|string|max:50',
        ]);

        $branch = Branch::create($data);

        // Clone products from the first existing branch (stock = 0)
        $source = Branch::where('id', '!=', $branch->id)->first();
        if ($source) {
            $products = DB::table('products')->where('branch', $source->name)->get();
            foreach ($products as $p) {
                $row = (array) $p;
                unset($row['id']);
                $row['branch']     = $branch->name;
                $row['stock']      = 0;
                $row['created_at'] = now();
                $row['updated_at'] = now();
                // Make barcode unique per branch: append suffix if set
                if (!empty($row['barcode'])) {
                    $row['barcode'] = $row['barcode'] . '-' . strtoupper(substr(str_replace(' ', '', $branch->name), 0, 3));
                }
                DB::table('products')->insert($row);
            }
        }

        return redirect()->back()->with('success', "Sucursal \"{$branch->name}\" creada exitosamente con {$products->count()} productos clonados.");
    }

    public function destroy(Branch $branch)
    {
        $this->authorizeSuperAdmin();

        // Safety: don't allow deleting a branch that has users or recent stock
        $userCount = DB::table('users')->where('branch', $branch->name)->count();
        if ($userCount > 0) {
            return redirect()->back()->withErrors(['error' => "No se puede eliminar: hay {$userCount} usuario(s) asignado(s) a esta sucursal."]);
        }

        $hasStock = DB::table('products')->where('branch', $branch->name)->where('stock', '>', 0)->exists();
        if ($hasStock) {
            return redirect()->back()->withErrors(['error' => 'No se puede eliminar: la sucursal tiene productos con stock disponible.']);
        }

        DB::table('products')->where('branch', $branch->name)->delete();
        $branch->delete();

        return redirect()->back()->with('success', "Sucursal \"{$branch->name}\" eliminada.");
    }

    private function authorizeSuperAdmin(): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);
    }
}

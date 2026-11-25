<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Assign 'Sucursal Managua' to all products without a branch
        DB::table('products')->whereNull('branch')->update(['branch' => 'Sucursal Managua']);

        // 2. Duplicate each product for 'Sucursal Leon' with stock = 0 if not already duplicated
        $managuaProducts = DB::table('products')->where('branch', 'Sucursal Managua')->get();
        $leonCount = DB::table('products')->where('branch', 'Sucursal Leon')->count();

        if ($leonCount === 0) {
            foreach ($managuaProducts as $p) {
                DB::table('products')->insert([
                    'name'                 => $p->name,
                    'description'          => $p->description,
                    'price'                => $p->price,
                    'cost'                 => $p->cost,
                    'purchase_tax_percent' => $p->purchase_tax_percent,
                    'utility_percent'      => $p->utility_percent,
                    'tax_percent'          => $p->tax_percent,
                    'image'                => $p->image,
                    'stock'                => 0,
                    'available'            => $p->available,
                    'reason_unavailable'   => $p->reason_unavailable,
                    'branch'               => 'Sucursal Leon',
                    'category_id'          => $p->category_id,
                    'barcode'              => $p->barcode,
                    'created_at'           => now(),
                    'updated_at'           => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        // Remove duplicated León products
        DB::table('products')->where('branch', 'Sucursal Leon')->delete();

        // Reset branch to null
        DB::table('products')->update(['branch' => null]);
    }
};

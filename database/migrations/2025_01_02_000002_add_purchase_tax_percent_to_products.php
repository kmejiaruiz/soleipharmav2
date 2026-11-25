<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('products', 'purchase_tax_percent')) {
            Schema::table('products', function (Blueprint $table) {
                $table->decimal('purchase_tax_percent', 5, 2)->default(15.00)->after('cost');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('products', 'purchase_tax_percent')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('purchase_tax_percent');
            });
        }
    }
};

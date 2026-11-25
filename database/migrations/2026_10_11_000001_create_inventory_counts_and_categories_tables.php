<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Categorías / Localizaciones ──────────────────────────────────────
        if (!Schema::hasTable('categories')) {
            Schema::create('categories', function (Blueprint $table) {
                $table->id();
                $table->string('name', 100);
                $table->string('code', 50)->nullable();
                $table->string('location', 100)->nullable()->comment('Pasillo, Estante o Localización en Bodega');
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }

        // ── 2. Conteo de Inventario (Preliminares y Aplicados) ───────────────────
        if (!Schema::hasTable('inventory_counts')) {
            Schema::create('inventory_counts', function (Blueprint $table) {
                $table->id();
                $table->string('count_number', 50)->unique();
                $table->string('branch', 100);
                $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
                $table->enum('status', ['preliminar', 'aplicado', 'cancelado'])->default('preliminar');
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->constrained('users');
                $table->foreignId('applied_by')->nullable()->constrained('users')->nullOnDelete();
                $table->integer('total_items_counted')->default(0);
                $table->integer('total_discrepancies')->default(0);
                $table->decimal('net_cost_difference', 12, 2)->default(0.00);
                $table->timestamp('applied_at')->nullable();
                $table->timestamps();
            });
        }

        // ── 3. Ítems del Conteo de Inventario ──────────────────────────────────
        if (!Schema::hasTable('inventory_count_items')) {
            Schema::create('inventory_count_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('inventory_count_id')->constrained('inventory_counts')->cascadeOnDelete();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->integer('system_stock')->default(0);
                $table->integer('counted_stock')->default(0);
                $table->integer('difference')->default(0);
                $table->decimal('unit_cost', 10, 2)->default(0.00);
                $table->decimal('total_cost_diff', 12, 2)->default(0.00);
                $table->string('notes')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_count_items');
        Schema::dropIfExists('inventory_counts');
        Schema::dropIfExists('categories');
    }
};

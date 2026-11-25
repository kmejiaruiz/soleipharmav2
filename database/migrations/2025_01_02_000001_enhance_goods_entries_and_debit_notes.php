<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Ampliar goods_entries con columnas faltantes ────────────────────
        Schema::table('goods_entries', function (Blueprint $table) {
            // Columnas que existen en el modelo pero no en la migración original
            if (!Schema::hasColumn('goods_entries', 'invoice_number')) {
                $table->string('invoice_number')->nullable()->after('id');
            }
            if (!Schema::hasColumn('goods_entries', 'supplier_id')) {
                $table->unsignedBigInteger('supplier_id')->nullable()->after('invoice_number');
                $table->foreign('supplier_id')->references('id')->on('suppliers')->nullOnDelete();
            }
            if (!Schema::hasColumn('goods_entries', 'notes')) {
                $table->text('notes')->nullable()->after('system_total');
            }
            if (!Schema::hasColumn('goods_entries', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->after('notes');
                $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('goods_entries', 'branch')) {
                $table->string('branch')->nullable()->after('created_by');
            }
            if (!Schema::hasColumn('goods_entries', 'iva_rate')) {
                $table->decimal('iva_rate', 5, 4)->default(0.15)->after('branch');
            }
        });

        // ── 2. Ampliar goods_entry_items con columnas de costo e IVA ──────────
        Schema::table('goods_entry_items', function (Blueprint $table) {
            if (!Schema::hasColumn('goods_entry_items', 'quantity_ordered')) {
                $table->integer('quantity_ordered')->default(0)->after('product_id');
            }
            if (!Schema::hasColumn('goods_entry_items', 'quantity_factured')) {
                $table->integer('quantity_factured')->nullable()->after('quantity_received');
            }
            if (!Schema::hasColumn('goods_entry_items', 'unit_cost')) {
                $table->decimal('unit_cost', 10, 2)->default(0)->after('quantity_factured');
            }
            if (!Schema::hasColumn('goods_entry_items', 'item_iva_rate')) {
                $table->decimal('item_iva_rate', 5, 4)->default(0.15)->after('unit_cost');
            }
            if (!Schema::hasColumn('goods_entry_items', 'missing_reason')) {
                $table->string('missing_reason')->nullable()->after('item_iva_rate');
            }
        });

        // ── 3. Crear tabla debit_notes ─────────────────────────────────────────
        if (!Schema::hasTable('debit_notes')) {
            Schema::create('debit_notes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('goods_entry_id')->constrained()->cascadeOnDelete();
                $table->foreignId('order_id')->constrained('product_orders')->cascadeOnDelete();
                $table->unsignedBigInteger('supplier_id')->nullable();
                $table->foreign('supplier_id')->references('id')->on('suppliers')->nullOnDelete();
                // Tipo de nota: 'costs' | 'quantities' | 'both'
                $table->enum('type', ['costs', 'quantities', 'both']);
                // Diferencia monetaria cuando factura > sistema
                $table->decimal('cost_difference', 10, 2)->default(0);
                // Costo extra por unidades de más recibidas (con IVA)
                $table->decimal('qty_extra_cost', 10, 2)->default(0);
                // Total nota = cost_difference + qty_extra_cost
                $table->decimal('total_amount', 10, 2)->default(0);
                // JSON con detalle por producto de los excesos de cantidad
                $table->json('details')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('debit_notes');

        Schema::table('goods_entry_items', function (Blueprint $table) {
            $table->dropColumn(['quantity_ordered', 'quantity_factured', 'unit_cost', 'item_iva_rate', 'missing_reason']);
        });

        Schema::table('goods_entries', function (Blueprint $table) {
            $table->dropColumn(['invoice_number', 'supplier_id', 'notes', 'created_by', 'branch', 'iva_rate']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Proveedores ────────────────────────────────────────────────────────
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('contact_name')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('supplier_catalog', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->decimal('cost_price', 10, 2)->nullable();
            $table->timestamps();
            $table->unique(['supplier_id', 'product_id']);
        });

        // ── Pedidos de resurtido (product_orders) ──────────────────────────────
        Schema::create('product_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('admin_name')->nullable();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('status', ['pending', 'applied', 'received'])->default('pending');
            $table->foreignId('applied_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('applied_at')->nullable();
            $table->timestamp('order_date')->useCurrent();
            $table->timestamps();
        });

        Schema::create('product_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('product_orders')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained();
            $table->integer('quantity')->default(1);
            $table->decimal('price', 10, 2)->nullable();
        });

        // ── Entrada de mercancía (goods_entries) ──────────────────────────────
        Schema::create('goods_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('product_orders');
            $table->foreignId('received_by')->constrained('users');
            $table->decimal('invoice_subtotal', 10, 2)->default(0);
            $table->decimal('invoice_tax', 10, 2)->default(0);
            $table->decimal('system_subtotal', 10, 2)->default(0);
            $table->decimal('system_tax', 10, 2)->default(0);
            $table->decimal('system_total', 10, 2)->default(0);
            $table->timestamp('received_at')->useCurrent();
            $table->timestamps();
        });

        Schema::create('goods_entry_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goods_entry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained();
            $table->integer('quantity_received');
            $table->text('justification')->nullable();
        });

        // ── Solicitudes de baja (discard_requests) ────────────────────────────
        Schema::create('discard_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained();
            $table->foreignId('requested_by')->constrained('users');
            $table->integer('quantity');
            $table->text('reason');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('decision_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('decision_reason')->nullable();
            $table->timestamp('decision_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discard_requests');
        Schema::dropIfExists('goods_entry_items');
        Schema::dropIfExists('goods_entries');
        Schema::dropIfExists('product_order_items');
        Schema::dropIfExists('product_orders');
        Schema::dropIfExists('supplier_catalog');
        Schema::dropIfExists('suppliers');
    }
};

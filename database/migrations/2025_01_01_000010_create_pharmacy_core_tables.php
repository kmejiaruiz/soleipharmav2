<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Productos ──────────────────────────────────────────────────────────
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->decimal('cost', 10, 2)->default(0.00);
            $table->decimal('utility_percent', 5, 2)->default(30.00);
            $table->decimal('tax_percent', 5, 2)->default(15.00);
            $table->string('image')->nullable();
            $table->integer('stock')->default(0);
            $table->boolean('available')->default(true);
            $table->text('reason_unavailable')->nullable();
            $table->string('branch')->nullable();          // sucursal
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->string('barcode')->nullable()->unique();
            $table->timestamps();
        });

        // ── Carrito ────────────────────────────────────────────────────────────
        Schema::create('cart', function (Blueprint $table) {
            $table->id();
            $table->string('session_id');
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->integer('quantity')->default(1);
            $table->timestamp('added_at')->useCurrent();
        });

        // ── Pedidos (ventas al cliente) ────────────────────────────────────────
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('total', 10, 2);
            $table->string('status')->default('pendiente');
            $table->string('client_name')->nullable();
            $table->string('payment_method')->nullable();
            $table->decimal('amount_paid', 10, 2)->nullable();
            $table->decimal('change_given', 10, 2)->nullable();
            $table->string('branch')->nullable();
            $table->foreignId('cash_session_id')->nullable();
            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained();
            $table->integer('quantity')->default(1);
            $table->decimal('price', 10, 2);
        });

        // ── Historial de Inventario ────────────────────────────────────────────
        Schema::create('inventory_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('admin_id')->constrained('users');
            $table->string('admin_name');
            $table->enum('change_type', ['edit', 'stock_increase', 'stock_decrease', 'supplier_entry']);
            $table->integer('previous_stock')->nullable();
            $table->integer('new_stock')->nullable();
            $table->text('description')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        // ── Notas de crédito / débito ──────────────────────────────────────────
        Schema::create('notes', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['credit', 'debit']);
            $table->string('note_number', 50);
            $table->timestamp('date')->useCurrent();
            $table->string('client_name');
            $table->string('client_document', 50);
            $table->string('client_address')->nullable();
            $table->text('description')->nullable();
            $table->decimal('amount', 10, 2);
            $table->foreignId('admin_id')->constrained('users');
            $table->string('admin_name');
            $table->enum('status', ['active', 'cancelled'])->default('active');
            $table->string('cancelled_by')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
        });

        // ── Carrusel ──────────────────────────────────────────────────────────
        Schema::create('carousels', function (Blueprint $table) {
            $table->id();
            $table->string('image');
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carousels');
        Schema::dropIfExists('notes');
        Schema::dropIfExists('inventory_log');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('cart');
        Schema::dropIfExists('products');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Caja: Sesiones de caja ─────────────────────────────────────────────
        Schema::create('cash_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('opened_by')->constrained('users');
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('opening_amount', 10, 2)->default(0.00);
            $table->enum('status', ['open', 'closed'])->default('open');
            $table->text('notes')->nullable();
            $table->string('branch')->nullable();
            $table->timestamp('opened_at')->useCurrent();
            $table->timestamp('closed_at')->nullable();
        });

        // ── Caja: Retiros ──────────────────────────────────────────────────────
        Schema::create('cash_withdrawals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('cash_sessions');
            $table->foreignId('withdrawn_by')->constrained('users');
            $table->decimal('total_amount', 10, 2);
            $table->json('denominations');
            $table->string('reason')->nullable();
            $table->timestamps();
        });

        // ── Caja: Arqueo de cierre ─────────────────────────────────────────────
        Schema::create('cash_closing_counts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->unique()->constrained('cash_sessions');
            $table->foreignId('counted_by')->constrained('users');
            $table->decimal('counted_amount', 10, 2);
            $table->json('denominations');
            $table->decimal('expected_amount', 10, 2);
            $table->decimal('difference', 10, 2);
            $table->timestamps();
        });

        // ── Bodega: stock por bodega ───────────────────────────────────────────
        Schema::create('bodega_stock', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->enum('bodega', ['debito', 'merma']);
            $table->integer('stock')->default(0);
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->unique(['product_id', 'bodega']);
        });

        // ── Bodega: movimientos ────────────────────────────────────────────────
        Schema::create('bodega_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained();
            $table->enum('from_bodega', ['sucursal', 'debito', 'merma']);
            $table->enum('to_bodega', ['sucursal', 'debito', 'merma']);
            $table->integer('quantity');
            $table->text('reason')->nullable();
            $table->foreignId('user_id')->constrained('users');
            $table->timestamp('created_at')->useCurrent();
        });

        // ── Traslados entre sucursales ─────────────────────────────────────────
        Schema::create('branch_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('from_branch');
            $table->string('to_branch');
            $table->enum('status', ['pendiente', 'recibido', 'cancelado'])->default('pendiente');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
        });

        Schema::create('branch_transfer_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transfer_id')->constrained('branch_transfers')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products');
            $table->integer('quantity_sent');
            $table->integer('quantity_received')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_transfer_items');
        Schema::dropIfExists('branch_transfers');
        Schema::dropIfExists('bodega_movements');
        Schema::dropIfExists('bodega_stock');
        Schema::dropIfExists('cash_closing_counts');
        Schema::dropIfExists('cash_withdrawals');
        Schema::dropIfExists('cash_sessions');
    }
};

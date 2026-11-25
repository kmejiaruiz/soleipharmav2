<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bodega_access_requests', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->string('branch');
            $table->string('status')->default('pending'); // pending, approved, rejected, cancelled
            $table->integer('responded_by_id')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->index(['branch', 'status']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bodega_access_requests');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('address')->nullable();
            $table->string('phone')->nullable();
            $table->timestamps();
        });

        // Seed with existing branches from products table
        $existing = DB::table('products')->select('branch')->distinct()->whereNotNull('branch')->pluck('branch');
        foreach ($existing as $name) {
            DB::table('branches')->insertOrIgnore(['name' => $name, 'created_at' => now(), 'updated_at' => now()]);
        }
        // Also seed from users
        $fromUsers = DB::table('users')->select('branch')->distinct()->whereNotNull('branch')->pluck('branch');
        foreach ($fromUsers as $name) {
            DB::table('branches')->insertOrIgnore(['name' => $name, 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};

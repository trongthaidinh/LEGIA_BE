<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('child_nav_id')->nullable()->constrained('child_navs')->onDelete('set null');
            $table->decimal('price', 10, 0);
            $table->decimal('original_price', 10, 0);
            $table->json('images');
            $table->json('features');
            $table->string('slug')->unique();
            $table->string('phone_number');
            $table->longText('content');
            $table->integer('available_stock')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};

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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email');
            $table->string('phone');
            $table->string('city');
            $table->string('district');
            $table->string('address');
            $table->text('note')->nullable();
            $table->enum('payment_method', ['atm', 'cod']);
            $table->integer('shipping_fee')->default(30000);
            $table->integer('subtotal');
            $table->integer('total');
            $table->enum('status', ['pending', 'completed', 'canceled'])->default('pending');
            $table->string('order_key')->unique();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};

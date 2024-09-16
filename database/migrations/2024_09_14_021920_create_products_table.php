<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('features')->nullable();
            $table->json('images')->nullable();
            $table->foreignId('child_nav_id')->nullable()->constrained('child_navs')->onDelete('set null');
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->text('summary')->nullable();
            $table->string('slug')->unique();
            $table->longText('content')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('products');
    }
}

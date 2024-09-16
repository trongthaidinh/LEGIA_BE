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
        Schema::create('child_navs_two', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->foreignId('parent_nav_id')->constrained('child_navs')->onDelete('cascade');
            $table->string('slug')->unique();
            $table->string('createdBy');
            $table->string('updatedBy');
            $table->timestamps();
            $table->integer('position');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('child_navs_twos');
    }
};

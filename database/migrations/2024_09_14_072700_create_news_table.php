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
        Schema::create('news', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->json('images')->nullable();
            $table->foreignId('child_nav_id')->nullable()->constrained('child_navs')->onDelete('set null');
            $table->string('createdBy')->nullable();
            $table->string('updatedBy')->nullable();
            $table->text('summary')->nullable();
            $table->string('slug')->unique();
            $table->longText('content')->nullable();
            $table->integer('views')->default(0);
            $table->boolean('isFeatured')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('news');
    }
};

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
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner')->constrained('users');
            $table->string('content', 300);
            $table->enum('privacy', ['PUBLIC', 'PRIVATE']);
            $table->enum('background', ['color', 'image']);
            $table->enum('post_type', ['AVATAR_CHANGE', 'COVER_CHANGE', 'STATUS', 'SHARE']);
            $table->boolean('is_saved')->default(false);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};

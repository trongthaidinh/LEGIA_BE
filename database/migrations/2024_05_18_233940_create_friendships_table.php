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
        Schema::create('friendships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained(
                table: 'users', indexName: 'friendships_owner_id'
            )->onUpdate('cascade')->onDelete('cascade')->nullable();
            $table->foreignId('friend_id')->constrained(
                table: 'users', indexName: 'friendships_friend_id'
            )->onUpdate('cascade')->onDelete('cascade')->nullable();
            $table->enum('status', ['pending', 'accepted'])->default('pending');
            $table->timestamp('created_at')->useCurrent()->nullable();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('friendships');
    }
};

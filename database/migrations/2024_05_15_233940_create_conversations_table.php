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
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('creator_id')->constrained(
                table: 'users', indexName: 'conversations_creator_id'
            )->onUpdate('cascade')->onDelete('cascade')->nullable();
            $table->string('name', 40)->nullable();
            $table->enum('type', ['individual', 'group'])->default('individual');
            $table->string('last_message', 300)->nullable();
            $table->timestamp('created_at')->useCurrent()->nullable();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};

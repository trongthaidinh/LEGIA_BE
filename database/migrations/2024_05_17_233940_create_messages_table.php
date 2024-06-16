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
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained(
                table: 'users', indexName: 'messages_user_id'
            );
            $table->foreignId('conversation_id')->constrained(
                table: 'conversations', indexName: 'messages_conversation_id'
            );
            $table->string('content', 300);
            $table->timestamp('read_at')->default(null)->nullable();
            $table->foreignId('deleted_by')->default(null)->constrained(
                table: 'users', indexName: 'messages_deleted_user_id'
            )->onUpdate('cascade')->onDelete('cascade')->nullable();
            $table->timestamp('created_at')->useCurrent()->nullable();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};

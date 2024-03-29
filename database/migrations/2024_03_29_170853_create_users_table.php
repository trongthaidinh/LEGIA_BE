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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('first_name', 30);
            $table->string('last_name', 20);
            $table->string('avatar')->nullable();
            $table->enum('gender', ['male', 'female', 'other']);
            $table->enum('role', ['admin', 'user']);
            $table->string('email', 30)->unique();
            $table->string('phone_number', 10)->unique();
            $table->string('address', 200)->nullable();
            $table->string('password');
            $table->date('date_of_birth')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};

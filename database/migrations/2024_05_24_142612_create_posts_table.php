<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePostsTable extends Migration
{
    public function up()
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('owner_id');
            $table->foreign('owner_id')->references('id')->on('users')->onDelete('cascade');
            $table->text('content')->nullable();
            $table->enum('privacy', ['PUBLIC', 'PRIVATE'])->default('PUBLIC');
            $table->unsignedBigInteger('background_id')->nullable();
            $table->foreign('background_id')->references('id')->on('backgrounds')->onDelete('set null');
            $table->enum('post_type', ['AVATAR_CHANGE', 'COVER_CHANGE', 'STATUS', 'SHARE']);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('posts');
    }
}

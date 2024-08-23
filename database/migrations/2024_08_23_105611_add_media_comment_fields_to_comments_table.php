<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMediaCommentFieldsToCommentsTable extends Migration
{
    public function up()
    {
        Schema::table('comments', function (Blueprint $table) {
            $table->unsignedBigInteger('post_image_comment_id')->nullable()->after('post_id');
            $table->unsignedBigInteger('post_video_comment_id')->nullable()->after('post_image_comment_id');
        });
    }

    public function down()
    {
        Schema::table('comments', function (Blueprint $table) {
            $table->dropColumn(['post_image_comment_id', 'post_video_comment_id']);
        });
    }
}

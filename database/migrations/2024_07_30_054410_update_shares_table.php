<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateSharesTable extends Migration
{
    public function up()
    {
        Schema::table('shares', function (Blueprint $table) {
            $table->unsignedBigInteger('owner_post_id')->after('post_id');
        });
    }

    public function down()
    {
        Schema::table('shares', function (Blueprint $table) {
            $table->dropColumn('owner_post_id');
        });
    }
}

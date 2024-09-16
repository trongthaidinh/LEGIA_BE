<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateParentNavsTable extends Migration
{
    public function up()
    {
        Schema::create('parent_navs', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('created_by');
            $table->string('updated_by');
            $table->timestamps();
            $table->integer('position');
        });
    }

    public function down()
    {
        Schema::dropIfExists('parent_navs');
    }
}

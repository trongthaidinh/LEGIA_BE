<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChildNavsTable extends Migration
{
    public function up()
    {
        Schema::create('child_navs', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->foreignId('parent_nav_id')->constrained('parent_navs')->onDelete('cascade');
            $table->string('slug')->unique();
            $table->string('createdBy');
            $table->string('updatedBy');
            $table->timestamps();
            $table->integer('position');
        });
    }

    public function down()
    {
        Schema::dropIfExists('child_navs');
    }
}

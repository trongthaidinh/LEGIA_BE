<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


class UpdateReportsTable extends Migration
{
    private $code = [
        '100',
        '101',
        '102',
        '103',
        '104',
        '105',
        '106',
        '108',
        '109',
        '110',
        '200',
        '201',
        '202',
        '203',
        '204',
        '205',
        '206',
        '208',
        '209',
        '210',
    ];

    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->enum('code', $this->code)->change();
            $table->string('description', 300)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropColumn('code');
            $table->dropColumn('description');
        });
    }
};

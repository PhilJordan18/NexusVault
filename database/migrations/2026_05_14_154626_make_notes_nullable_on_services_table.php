<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('services', function (Blueprint $table) {
            $table->string('notes')->nullable()->change();
            $table->string('notes_iv')->nullable()->change();
            $table->string('notes_tag')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('services', function (Blueprint $table) {
            $table->string('notes')->nullable(false)->change();
            $table->string('notes_iv')->nullable(false)->change();
            $table->string('notes_tag')->nullable(false)->change();
        });
    }
};

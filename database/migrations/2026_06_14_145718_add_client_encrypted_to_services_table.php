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
        Schema::table('services', function (Blueprint $table) {
            $table->text('username')->change();
            $table->text('password')->change();
            $table->text('notes')->nullable()->change();
            $table->boolean('client_encrypted')->default(false)->after('notes_tag');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn('client_encrypted');
            $table->string('username')->change();
            $table->string('password')->change();
            $table->string('notes')->nullable()->change();
        });
    }
};

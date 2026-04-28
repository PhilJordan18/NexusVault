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
            $table->string('username_iv', 24)->after('username');
            $table->string('username_tag', 32)->after('username_iv');
            $table->string('notes_iv', 24)->nullable()->after('notes');
            $table->string('notes_tag', 32)->nullable()->after('notes_iv');
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn(['username_iv', 'username_tag', 'notes_iv', 'notes_tag']);
        });
    }
};

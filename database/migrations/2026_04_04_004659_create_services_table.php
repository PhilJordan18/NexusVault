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
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('url')->nullable();
            $table->string('favicon')->nullable();

            $table->string('username');
            $table->string('password');
            $table->string('notes');

            $table->string('iv', 64);
            $table->string('tag', 64);

            $table->foreignId('shared_user_id')->nullable()->constrained('users');
            $table->timestamp('shared_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};

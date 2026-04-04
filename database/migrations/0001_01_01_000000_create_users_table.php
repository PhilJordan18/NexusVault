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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('salt', 64);
            $table->text('public_key');
            $table->text('private_key');
            $table->boolean('mfa_enabled')->default(false);
            $table->text('totp_secret');
            $table->rememberToken();
            $table->timestamps();
        });


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

        Schema::create('shares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained()->onDelete('cascade');
            $table->foreignId('from_user_id')->constrained('users');
            $table->foreignId('to_user_id')->constrained('users');
            $table->timestamp('shared_at')->useCurrent();
            $table->timestamp('accepted_at')->nullable();
            $table->boolean('rejected')->default(false);
            $table->text('shared_data');
            $table->timestamps();
        });

        Schema::create('passkeys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('credential_id');
            $table->text('public_key');
            $table->string('user_handle');
            $table->timestamp('last_used_at');
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('passkeys');
        Schema::dropIfExists('shares');
        Schema::dropIfExists('services');
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};

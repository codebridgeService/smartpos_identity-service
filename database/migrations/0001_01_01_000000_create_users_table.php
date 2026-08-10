<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            // Primary key
            $table->id();

            // Public/API identifier
            $table->uuid('uuid')->unique();

            // User information
            $table->string('name', 150);

            $table->string('username', 100)
                ->nullable()
                ->unique();

            $table->string('email', 150)
                ->nullable()
                ->unique();

            $table->string('phone', 30)
                ->nullable()
                ->unique();

            // Authentication
            $table->string('password', 255)->nullable();

            // Profile
            $table->string('avatar', 255)->nullable();

            // Account status
            $table->string('status', 30)
                ->default('active');

            // Verification
            $table->timestamp('email_verified_at')
                ->nullable();

            // Login information
            $table->timestamp('last_login_at')
                ->nullable();

            $table->string('last_login_ip', 45)
                ->nullable();

            // Laravel timestamps
            $table->timestamps();

            // deleted_at
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
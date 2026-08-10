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
        Schema::create('user_sessions', function (Blueprint $table) {
            $table->id();
        
            $table->uuid('uuid')->unique();
        
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();
        
            $table->foreignId('user_device_id')
                ->nullable()
                ->constrained('user_devices')
                ->nullOnDelete();
        
            $table->string(
                'refresh_token_hash',
                255
            );
        
            $table->string('ip_address', 45)
                ->nullable();
        
            $table->text('user_agent')
                ->nullable();
        
            $table->timestamp('last_activity_at')
                ->nullable();
        
            $table->timestamp('expires_at');
        
            $table->timestamp('revoked_at')
                ->nullable();
        
            $table->timestamps();
        
            $table->index('user_id');
            $table->index('expires_at');
            $table->index('revoked_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_sessions');
    }
};

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
        Schema::create('auth_otps', function (Blueprint $table) {
            $table->id();
        
            $table->uuid('uuid')->unique();
        
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
        
            $table->string('channel', 20);
        
            // email or phone number
            $table->string('identifier', 150);
        
            $table->string('purpose', 30);
        
            $table->string('code_hash', 255);
        
            $table->timestamp('expires_at');
        
            $table->timestamp('verified_at')
                ->nullable();
        
            $table->unsignedInteger('attempts')
                ->default(0);
        
            // Generated after OTP verification
            $table->string('reset_token_hash', 255)
                ->nullable();
        
            $table->timestamp('reset_token_expires_at')
                ->nullable();
        
            $table->timestamps();
        
            $table->index([
                'identifier',
                'purpose'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auth_otps');
    }
};

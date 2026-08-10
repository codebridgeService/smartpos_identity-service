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
        Schema::create('login_attempts', function (Blueprint $table) {
            $table->id();
        
            $table->uuid('uuid')->unique();
        
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
        
            $table->string('identifier', 150)
                ->nullable();
        
            $table->string('ip_address', 45)
                ->nullable();
        
            $table->text('user_agent')
                ->nullable();
        
            $table->string('status', 30);
        
            $table->string('failure_reason', 100)
                ->nullable();
        
            $table->timestamp('attempted_at')
                ->useCurrent();
        
            $table->index('user_id');
            $table->index('ip_address');
            $table->index('attempted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('login_attempts');
    }
};

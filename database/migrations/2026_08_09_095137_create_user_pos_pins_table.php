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
        Schema::create('user_pos_pins', function (Blueprint $table) {
            $table->id();
        
            $table->uuid('uuid')->unique();
        
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();
        
            // External reference to Business Service
            $table->uuid('business_uuid')->index();
        
            $table->string('pin_hash', 255);
        
            $table->boolean('is_active')
                ->default(true);
        
            $table->unsignedInteger('failed_attempts')
                ->default(0);
        
            $table->timestamp('locked_until')
                ->nullable();
        
            $table->timestamps();
        
            $table->unique([
                'user_id',
                'business_uuid'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_pos_pins');
    }
};

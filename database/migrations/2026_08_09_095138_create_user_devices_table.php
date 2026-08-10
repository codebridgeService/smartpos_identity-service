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
        Schema::create('user_devices', function (Blueprint $table) {
            $table->id();
        
            $table->uuid('uuid')->unique();
        
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();
        
            $table->string('device_uuid', 150);
        
            $table->string('device_name', 150)
                ->nullable();
        
            $table->string('device_type', 50)
                ->nullable();
        
            $table->string('platform', 50)
                ->nullable();
        
            $table->string('first_ip_address', 45)
                ->nullable();
        
            $table->string('last_ip_address', 45)
                ->nullable();
        
            $table->boolean('is_trusted')
                ->default(false);
        
            $table->boolean('is_blocked')
                ->default(false);
        
            $table->timestamp('last_seen_at')
                ->nullable();
        
            $table->timestamps();
        
            $table->unique([
                'user_id',
                'device_uuid'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_devices');
    }
};

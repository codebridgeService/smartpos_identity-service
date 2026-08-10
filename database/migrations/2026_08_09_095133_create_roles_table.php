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
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
        
            $table->uuid('uuid')->unique();
        
            // External Business Service UUID.
            // NO foreign key to businesses table.
            $table->uuid('business_uuid')
                ->nullable()
                ->index();
        
            $table->string('name', 100);
            $table->string('code', 100);
        
            $table->boolean('is_system')
                ->default(false);
        
            $table->timestamps();
        
            $table->unique([
                'business_uuid',
                'code'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};

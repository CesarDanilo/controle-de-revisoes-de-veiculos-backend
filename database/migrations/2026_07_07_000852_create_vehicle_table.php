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
        Schema::create('vehicle', function (Blueprint $table) {
            $table->id();
            $table->string('model', 255);
            $table->string('year', 4);
            $table->string('color', 255);
            $table->foreignId('brand_id')->constrained('brands')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('people_id')->constrained('people')->onDelete('cascade');
            $table->string('license_plate', 10);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle');
    }
};

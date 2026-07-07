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
        Schema::create('revisions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignUuid('vehicle_id')->constrained('vehicle')->onDelete('cascade');
            $table->string('description', 255);
            $table->date('revision_date');
            $table->decimal('cost', 10, 2);
            $table->string('next_revision_date', 10);
            $table->string('next_revision_km', 10);
            $table->string('km', 10);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('revisions');
    }
};

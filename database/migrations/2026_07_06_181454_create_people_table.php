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
        Schema::create('people', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('user_id')
                ->constrained('users')
                ->onDelete('cascade');

            $table->string('name');
            $table->string('email');
            $table->string('document'); // CPF
            $table->string('phone')->nullable();

            $table->date('birth_date');
            $table->enum('gender', ['M', 'F']);

            $table->timestamps();

            $table->unique(['user_id', 'email']);
            $table->unique(['user_id', 'document']);
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('people');
    }
};
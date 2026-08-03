<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_exports', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('user_id')
                ->constrained('users')
                ->onDelete('cascade');

            // 'full' | 'revisions' | 'vehicles' | 'people'
            $table->string('type', 30);

            // 'pending' | 'processing' | 'done' | 'failed'
            $table->string('status', 20)->default('pending');

            // filtros usados na geração (ex: {"start": "...", "end": "..."})
            $table->json('params')->nullable();

            $table->string('file_path')->nullable();
            $table->text('error_message')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_exports');
    }
};
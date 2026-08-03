<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            // 1. Remove a constraint única global atual na coluna 'name'
            $table->dropUnique('brands_name_unique'); // ou $table->dropUnique(['name']);

            // 2. Cria a constraint única composta (user_id + name)
            $table->unique(['user_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'name']);
            $table->unique('name');
        });
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Ajusta nullable + tipo das colunas que já estão certas (sem perigo de perda)
        Schema::table('revisions', function (Blueprint $table) {
            $table->string('description', 255)->nullable()->change();
            $table->decimal('cost', 10, 2)->nullable()->change();
        });

        // 2. Adiciona colunas temporárias com o tipo correto
        Schema::table('revisions', function (Blueprint $table) {
            $table->date('next_revision_date_tmp')->nullable();
            $table->unsignedInteger('next_revision_km_tmp')->nullable();
            $table->unsignedInteger('km_tmp')->nullable();
        });

        // 3. Copia e converte os dados existentes
        DB::table('revisions')->orderBy('id')->chunk(100, function ($rows) {
            foreach ($rows as $row) {
                DB::table('revisions')
                    ->where('id', $row->id)
                    ->update([
                        'next_revision_date_tmp' => $row->next_revision_date ?: null,
                        'next_revision_km_tmp' => is_numeric($row->next_revision_km) ? (int) $row->next_revision_km : null,
                        'km_tmp' => is_numeric($row->km) ? (int) $row->km : null,
                    ]);
            }
        });

        // 4. Remove as colunas antigas
        Schema::table('revisions', function (Blueprint $table) {
            $table->dropColumn(['next_revision_date', 'next_revision_km', 'km']);
        });

        // 5. Renomeia as colunas temporárias para o nome final
        Schema::table('revisions', function (Blueprint $table) {
            $table->renameColumn('next_revision_date_tmp', 'next_revision_date');
            $table->renameColumn('next_revision_km_tmp', 'next_revision_km');
            $table->renameColumn('km_tmp', 'km');
        });
    }

    public function down(): void
    {
        // Reverter é arriscado pois perde a tipagem; recomenda-se restaurar via backup
        Schema::table('revisions', function (Blueprint $table) {
            $table->string('description', 255)->nullable(false)->change();
            $table->decimal('cost', 10, 2)->nullable(false)->change();
        });
    }
};
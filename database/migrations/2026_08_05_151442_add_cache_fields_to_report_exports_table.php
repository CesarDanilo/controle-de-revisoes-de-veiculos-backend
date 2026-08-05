<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adiciona:
     * - params_hash: hash MD5 dos parâmetros (type + start + end), usado
     *   para encontrar rapidamente exports "iguais" sem comparar JSON inteiro.
     * - data_version: número da versão dos dados no momento em que esse
     *   export foi gerado. Se a versão atual do sistema for maior que essa,
     *   o export é considerado "stale" (desatualizado) e não deve ser reaproveitado.
     */
    public function up(): void
    {
        Schema::table('report_exports', function (Blueprint $table) {
            $table->string('params_hash', 32)->nullable()->after('params');
            $table->unsignedBigInteger('data_version')->default(1)->after('params_hash');

            // Acelera a busca por "já existe um export igual e válido?"
            $table->index(['user_id', 'type', 'params_hash', 'data_version', 'status'], 'report_exports_cache_lookup_idx');
        });
    }

    public function down(): void
    {
        Schema::table('report_exports', function (Blueprint $table) {
            $table->dropIndex('report_exports_cache_lookup_idx');
            $table->dropColumn(['params_hash', 'data_version']);
        });
    }
};
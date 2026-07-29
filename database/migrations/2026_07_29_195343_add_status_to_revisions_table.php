<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('revisions', function (Blueprint $table) {
            // string, não enum nativo do banco — assim mudar/adicionar
            // status no futuro é só código (enum PHP em App\Enums), sem
            // precisar de ALTER TYPE / MODIFY COLUMN no banco.
            $table->string('status', 30)->default('aberto')->after('cost');
            $table->string('status_pagamento', 20)->default('pendente')->after('status');

            // toda listagem do Kanban filtra por usuário + status ao
            // mesmo tempo (ex: "minhas revisões 'em_andamento'"), então o
            // índice precisa ser nessa ordem.
            $table->index(['user_id', 'status'], 'revisions_user_id_status_index');
        });

        // Rede de segurança: mesmo que um bug ou uma chamada direta na API
        // tente gravar um valor fora da lista, o banco recusa.
        // Se adicionar/renomear um status no enum PHP, crie uma migration
        // nova pra atualizar esta constraint também (senão o insert falha).
        DB::statement("
            ALTER TABLE revisions
            ADD CONSTRAINT revisions_status_check
            CHECK (status IN ('aberto', 'em_andamento', 'aguardando_pagamento', 'concluido', 'cancelado'))
        ");

        DB::statement("
            ALTER TABLE revisions
            ADD CONSTRAINT revisions_status_pagamento_check
            CHECK (status_pagamento IN ('pendente', 'pago'))
        ");
    }

    public function down(): void
    {
        Schema::table('revisions', function (Blueprint $table) {
            $table->dropIndex('revisions_user_id_status_index');
            $table->dropColumn(['status', 'status_pagamento']);
        });
    }
};
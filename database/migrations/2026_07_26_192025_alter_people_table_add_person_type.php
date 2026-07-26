<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // birth_date: coluna comum (date), o Doctrine lida bem com ->change() aqui
        Schema::table('people', function (Blueprint $table) {
            $table->date('birth_date')->nullable()->change();
        });

        // gender: NÃO usar ->change() aqui (Doctrine gera SQL inválido no Postgres
        // quando a coluna tem CHECK constraint). Fazemos via SQL puro.
        DB::statement('ALTER TABLE people ALTER COLUMN gender DROP NOT NULL');

        // adiciona a coluna de tipo de pessoa
        Schema::table('people', function (Blueprint $table) {
            $table->enum('person_type', ['PF', 'PJ'])->default('PF')->after('document');
        });

        // preenche retroativamente os registros existentes
        DB::statement("
            UPDATE people
            SET person_type = CASE
                WHEN length(document) = 14 THEN 'PJ'
                ELSE 'PF'
            END
        ");
    }

    public function down(): void
    {
        Schema::table('people', function (Blueprint $table) {
            $table->dropColumn('person_type');
        });

        DB::statement("ALTER TABLE people ALTER COLUMN gender SET NOT NULL");

        Schema::table('people', function (Blueprint $table) {
            $table->date('birth_date')->nullable(false)->change();
        });
    }
};
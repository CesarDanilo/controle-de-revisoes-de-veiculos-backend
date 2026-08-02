<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * ⚠️ IMPORTANTE — rode `php artisan data:audit-invalid --fix` ANTES desta
 * migration. Se já existir alguma linha com model/name/document/placa em
 * branco, o ALTER TABLE abaixo vai falhar (o Postgres valida as linhas
 * existentes contra a constraint no momento em que ela é criada).
 *
 * Isso é a segunda camada de proteção: mesmo que um registro seja
 * inserido direto no banco (seeder, tinker, import manual, script),
 * pulando completamente a validação da aplicação (FormRequests), essas
 * constraints impedem que strings vazias ou só com espaço em branco
 * cheguem nos campos que o frontend precisa pra renderizar corretamente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE vehicle ADD CONSTRAINT vehicle_model_not_blank_check CHECK (btrim(model) <> '')");
        DB::statement("ALTER TABLE vehicle ADD CONSTRAINT vehicle_license_plate_not_blank_check CHECK (btrim(license_plate) <> '')");
        DB::statement("ALTER TABLE people ADD CONSTRAINT people_name_not_blank_check CHECK (btrim(name) <> '')");
        DB::statement("ALTER TABLE people ADD CONSTRAINT people_document_not_blank_check CHECK (btrim(document) <> '')");
        DB::statement("ALTER TABLE brands ADD CONSTRAINT brands_name_not_blank_check CHECK (btrim(name) <> '')");
        DB::statement("ALTER TABLE colors ADD CONSTRAINT colors_name_not_blank_check CHECK (btrim(name) <> '')");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE vehicle DROP CONSTRAINT vehicle_model_not_blank_check');
        DB::statement('ALTER TABLE vehicle DROP CONSTRAINT vehicle_license_plate_not_blank_check');
        DB::statement('ALTER TABLE people DROP CONSTRAINT people_name_not_blank_check');
        DB::statement('ALTER TABLE people DROP CONSTRAINT people_document_not_blank_check');
        DB::statement('ALTER TABLE brands DROP CONSTRAINT brands_name_not_blank_check');
        DB::statement('ALTER TABLE colors DROP CONSTRAINT colors_name_not_blank_check');
    }
};
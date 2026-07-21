<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE people DROP CONSTRAINT people_gender_check');

        DB::statement("ALTER TABLE people ADD CONSTRAINT people_gender_check CHECK (gender IN ('M', 'F', 'O'))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE people DROP CONSTRAINT people_gender_check');

        DB::statement("ALTER TABLE people ADD CONSTRAINT people_gender_check CHECK (gender IN ('M', 'F'))");
    }
};
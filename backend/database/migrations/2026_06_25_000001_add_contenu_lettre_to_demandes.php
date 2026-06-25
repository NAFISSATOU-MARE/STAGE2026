<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('demandes', function (Blueprint $table) {
            // Contenu personnalisable de la lettre de congé (texte, dates, formules).
            // Stocké en JSON, fusionné avec les données de la demande lors du rendu PDF.
            $table->json('contenu_lettre')->nullable()->after('decision_reference_id');
        });
    }

    public function down(): void
    {
        Schema::table('demandes', function (Blueprint $table) {
            $table->dropColumn('contenu_lettre');
        });
    }
};

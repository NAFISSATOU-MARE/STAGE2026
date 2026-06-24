<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('demandes', function (Blueprint $table) {
            // Durée de validité en jours (1-90), fixée lors de l'approbation finale d'une DECISION.
            $table->unsignedSmallInteger('duree_jours')->nullable()->after('date_validation');
        });
    }

    public function down(): void
    {
        Schema::table('demandes', function (Blueprint $table) {
            $table->dropColumn('duree_jours');
        });
    }
};

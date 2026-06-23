<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demandes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained('agents');
            $table->enum('type', ['CONGE', 'DECISION']);
            $table->date('date_debut');
            $table->date('date_fin');
            $table->unsignedSmallInteger('nombre_jours');
            $table->text('motif');
            $table->string('lieu_jouissance')->nullable();
            $table->enum('statut', ['EN_ATTENTE', 'APPROUVEE', 'REJETEE'])->default('EN_ATTENTE');
            $table->tinyInteger('niveau_courant')->default(1);
            $table->unsignedSmallInteger('annee');
            $table->string('numero_reference')->nullable()->unique();
            // Lien vers la décision active pour un CONGE d'un AGENT_ETAT
            $table->foreignId('decision_reference_id')->nullable()->constrained('demandes')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demandes');
    }
};

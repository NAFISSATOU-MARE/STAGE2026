<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agents', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('prenom');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->foreignId('direction_id')->nullable()->constrained('directions')->nullOnDelete();
            $table->foreignId('division_id')->nullable()->constrained('divisions')->nullOnDelete();
            $table->string('poste');
            $table->string('corps')->nullable();
            $table->enum('profil', ['CONTRACTUEL', 'AGENT_ETAT'])->default('CONTRACTUEL');
            $table->string('matricule')->nullable()->unique();
            $table->enum('role', ['AGENT', 'CHEF_DIVISION', 'DIRECTEUR', 'DAP', 'DRH', 'ADMIN', 'ADMIN_DIRECTION'])->default('AGENT');
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agents');
    }
};

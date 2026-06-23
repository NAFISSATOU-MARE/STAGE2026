<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('validations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('demande_id')->constrained('demandes')->cascadeOnDelete();
            $table->foreignId('valideur_id')->constrained('agents');
            $table->tinyInteger('niveau');
            $table->enum('avis', ['FAVORABLE', 'DEFAVORABLE']);
            $table->text('motif_refus')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('validations');
    }
};

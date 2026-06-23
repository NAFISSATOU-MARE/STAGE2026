<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('divisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('direction_id')->constrained()->cascadeOnDelete();
            $table->string('sigle', 15);
            $table->string('nom');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('divisions');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('directions', function (Blueprint $table) {
            $table->id();
            $table->string('sigle', 10)->unique();
            $table->string('nom');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('directions');
    }
};

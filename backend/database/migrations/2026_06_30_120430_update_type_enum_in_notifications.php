<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF');

            Schema::create('notifications_new', function ($table) {
                $table->id();
                $table->foreignId('agent_id')->constrained('agents')->cascadeOnDelete();
                $table->string('type', 50);
                $table->foreignId('demande_id')->nullable()->constrained('demandes')->nullOnDelete();
                $table->string('message');
                $table->boolean('lu')->default(false);
                $table->timestamps();
            });

            DB::statement('INSERT INTO notifications_new SELECT * FROM notifications');
            DB::statement('DROP TABLE notifications');
            DB::statement('ALTER TABLE notifications_new RENAME TO notifications');

            DB::statement('PRAGMA foreign_keys = ON');
        } else {
            Schema::table('notifications', function ($table) {
                $table->string('type', 50)->change();
            });
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF');

            Schema::create('notifications_old', function ($table) {
                $table->id();
                $table->foreignId('agent_id')->constrained('agents')->cascadeOnDelete();
                $table->enum('type', ['VALIDATION_REQUISE', 'VALIDATION_RECUE', 'REJET_RECU']);
                $table->foreignId('demande_id')->nullable()->constrained('demandes')->nullOnDelete();
                $table->string('message');
                $table->boolean('lu')->default(false);
                $table->timestamps();
            });

            DB::statement('INSERT INTO notifications_old SELECT * FROM notifications');
            DB::statement('DROP TABLE notifications');
            DB::statement('ALTER TABLE notifications_old RENAME TO notifications');

            DB::statement('PRAGMA foreign_keys = ON');
        } else {
            Schema::table('notifications', function ($table) {
                $table->enum('type', ['VALIDATION_REQUISE', 'VALIDATION_RECUE', 'REJET_RECU'])->change();
            });
        }
    }
};

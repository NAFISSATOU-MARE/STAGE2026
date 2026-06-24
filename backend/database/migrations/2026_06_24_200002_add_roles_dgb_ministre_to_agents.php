<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const NEW_ROLES = ['AGENT','CHEF_DIVISION','DIRECTEUR','DAP','DRH','ADMIN','ADMIN_DIRECTION','DGB','MINISTRE'];
    private const OLD_ROLES = ['AGENT','CHEF_DIVISION','DIRECTEUR','DAP','DRH','ADMIN','ADMIN_DIRECTION'];

    public function up(): void
    {
        $this->changeRoleEnum(self::NEW_ROLES);
    }

    public function down(): void
    {
        $this->changeRoleEnum(self::OLD_ROLES);
    }

    private function changeRoleEnum(array $roles): void
    {
        $driver = DB::getDriverName();

        // ── MySQL / MariaDB ────────────────────────────────────────────────────
        if (\in_array($driver, ['mysql', 'mariadb'], true)) {
            $list = implode(',', array_map(fn($r) => "'$r'", $roles));
            DB::statement("ALTER TABLE agents MODIFY COLUMN role ENUM($list) NOT NULL DEFAULT 'AGENT'");
            return;
        }

        // ── SQLite : reconstruction de la table ────────────────────────────────
        // SQLite ne supporte pas MODIFY COLUMN ; on recrée la table avec la
        // nouvelle contrainte CHECK et on y copie les données.
        DB::statement('PRAGMA foreign_keys = OFF');

        Schema::create('agents_new', function (Blueprint $table) use ($roles) {
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
            $table->enum('role', $roles)->default('AGENT');
            $table->rememberToken();
            $table->timestamps();
            $table->boolean('must_change_password')->default(false);
        });

        $cols = implode(',', [
            'id', 'nom', 'prenom', 'email', 'email_verified_at', 'password',
            'direction_id', 'division_id', 'poste', 'corps', 'profil', 'matricule',
            'role', 'remember_token', 'created_at', 'updated_at', 'must_change_password',
        ]);

        DB::statement("INSERT INTO agents_new ($cols) SELECT $cols FROM agents");

        Schema::drop('agents');
        DB::statement('ALTER TABLE agents_new RENAME TO agents');

        DB::statement('PRAGMA foreign_keys = ON');
    }
};

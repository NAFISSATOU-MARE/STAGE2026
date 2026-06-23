<?php

namespace Database\Seeders;

use App\Models\Direction;
use Illuminate\Database\Seeder;

class DirectionSeeder extends Seeder
{
    public function run(): void
    {
        $directions = [
            ['sigle' => 'DAP',  'nom' => "Direction de l'Administration et du Personnel"],
            ['sigle' => 'DCI',  'nom' => "Direction du Contrôle Interne"],
            ['sigle' => 'DSI',  'nom' => "Direction des Systèmes d'Information"],
            ['sigle' => 'DPB',  'nom' => "Direction de la Programmation Budgétaire"],
            ['sigle' => 'DCB',  'nom' => "Direction du Contrôle Budgétaire"],
            ['sigle' => 'DODP', 'nom' => "Direction de l'Ordonnancement des Dépenses Publiques"],
            ['sigle' => 'DS',   'nom' => "Direction de la Solde"],
            ['sigle' => 'DP',   'nom' => "Direction des Pensions"],
            ['sigle' => 'DMTA', 'nom' => "Direction du Matériel et du Transit Administratif"],
            ['sigle' => 'CSS',  'nom' => "Cellule de Suivi et de Synthèse"],
            ['sigle' => 'CER',  'nom' => "Cellule des Études et de la Réglementation"],
        ];

        foreach ($directions as $data) {
            Direction::firstOrCreate(['sigle' => $data['sigle']], $data);
        }
    }
}

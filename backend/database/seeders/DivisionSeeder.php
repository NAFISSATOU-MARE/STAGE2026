<?php

namespace Database\Seeders;

use App\Models\Direction;
use App\Models\Division;
use Illuminate\Database\Seeder;

class DivisionSeeder extends Seeder
{
    public function run(): void
    {
        $structure = [
            'DAP' => [
                ['sigle' => 'DGRH',   'nom' => "Division de la Gestion des Ressources Humaines"],
                ['sigle' => 'DMG',    'nom' => "Division du Matériel et de la Gestion"],
                ['sigle' => 'DAD',    'nom' => "Division de l'Administration et de la Documentation"],
                ['sigle' => 'BAF',    'nom' => "Bureau des Affaires Financières"],
            ],
            'DCI' => [
                ['sigle' => 'DSD',    'nom' => "Division du Suivi et de la Documentation"],
                ['sigle' => 'DACG',   'nom' => "Division de l'Audit et du Contrôle de Gestion"],
                ['sigle' => 'DSP',    'nom' => "Division des Statistiques et de la Prospective"],
                ['sigle' => 'BAF',    'nom' => "Bureau des Affaires Financières"],
            ],
            'DSI' => [
                ['sigle' => 'DED',    'nom' => "Division de l'Exploitation et du Développement"],
                ['sigle' => 'DEM',    'nom' => "Division de l'Équipement et de la Maintenance"],
                ['sigle' => 'DCQRU',  'nom' => "Division de la Conception, de la Qualité et de la Réglementation de l'Usage"],
                ['sigle' => 'DPI',    'nom' => "Division des Projets Informatiques"],
                ['sigle' => 'BAF',    'nom' => "Bureau des Affaires Financières"],
            ],
            'DPB' => [
                ['sigle' => 'DS',     'nom' => "Division de la Synthèse"],
                ['sigle' => 'DSE',    'nom' => "Division des Services de l'État"],
                ['sigle' => 'DSSOC',  'nom' => "Division des Secteurs Sociaux"],
                ['sigle' => 'DSSOUV', 'nom' => "Division des Souverainetés"],
                ['sigle' => 'CI',     'nom' => "Cellule Informatique"],
                ['sigle' => 'BAF',    'nom' => "Bureau des Affaires Financières"],
            ],
            'DCB' => [
                ['sigle' => 'DS',     'nom' => "Division de la Synthèse"],
                ['sigle' => 'DCR',    'nom' => "Division du Contrôle des Recettes"],
                ['sigle' => 'DCP',    'nom' => "Division du Contrôle des Paiements"],
                ['sigle' => 'CI',     'nom' => "Cellule Informatique"],
                ['sigle' => 'BAF',    'nom' => "Bureau des Affaires Financières"],
            ],
            'DODP' => [
                ['sigle' => 'DODPER', 'nom' => "Division de l'Ordonnancement des Dépenses du Personnel"],
                ['sigle' => 'DODFI',  'nom' => "Division de l'Ordonnancement des Dépenses de Fonctionnement et d'Investissement"],
                ['sigle' => 'DSCE',   'nom' => "Division du Suivi et du Contrôle des Engagements"],
                ['sigle' => 'BAF',    'nom' => "Bureau des Affaires Financières"],
            ],
            'DS' => [
                ['sigle' => 'DGCP',   'nom' => "Division de la Gestion de la Carrière du Personnel"],
                ['sigle' => 'DTR',    'nom' => "Division du Traitement"],
                ['sigle' => 'DRR',    'nom' => "Division de la Réglementation et du Recours"],
                ['sigle' => 'BAF',    'nom' => "Bureau des Affaires Financières"],
            ],
            'DP' => [
                ['sigle' => 'DPC',    'nom' => "Division des Pensions Civiles"],
                ['sigle' => 'DPM',    'nom' => "Division des Pensions Militaires"],
                ['sigle' => 'DRAD',   'nom' => "Division des Rentes d'Accidents et de Décès"],
                ['sigle' => 'BAF',    'nom' => "Bureau des Affaires Financières"],
            ],
            'DMTA' => [
                ['sigle' => 'DAA',    'nom' => "Division des Approvisionnements et des Achats"],
                ['sigle' => 'DSD',    'nom' => "Division du Suivi et de la Distribution"],
                ['sigle' => 'DTP',    'nom' => "Division du Transport et du Parc"],
                ['sigle' => 'BAF',    'nom' => "Bureau des Affaires Financières"],
            ],
            'CSS' => [
                ['sigle' => 'SCAD',   'nom' => "Section de Collecte et d'Analyse des Données"],
                ['sigle' => 'SRNRS',  'nom' => "Section des Rapports et Notes de Restitution et de Synthèse"],
                ['sigle' => 'SSRD',   'nom' => "Section du Suivi et de la Répartition des Dotations"],
                ['sigle' => 'BAF',    'nom' => "Bureau des Affaires Financières"],
            ],
            'CER' => [
                ['sigle' => 'SEEF',   'nom' => "Section des Études Économiques et Fiscales"],
                ['sigle' => 'SEMJR',  'nom' => "Section des Études de Mise en Œuvre Juridique et Réglementaire"],
                ['sigle' => 'SEVJI',  'nom' => "Section des Études de Veille Juridique et d'Innovation"],
                ['sigle' => 'BAF',    'nom' => "Bureau des Affaires Financières"],
            ],
        ];

        foreach ($structure as $directionSigle => $divisions) {
            $direction = Direction::where('sigle', $directionSigle)->firstOrFail();
            foreach ($divisions as $data) {
                Division::firstOrCreate(
                    ['direction_id' => $direction->id, 'sigle' => $data['sigle']],
                    ['nom' => $data['nom']]
                );
            }
        }
    }
}

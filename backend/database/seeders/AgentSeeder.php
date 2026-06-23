<?php

namespace Database\Seeders;

use App\Models\Agent;
use App\Models\Direction;
use App\Models\Division;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AgentSeeder extends Seeder
{
    public function run(): void
    {
        // ─── Helpers ──────────────────────────────────────────────────────────
        $dir = fn(string $sigle) => Direction::where('sigle', $sigle)->firstOrFail();
        $div = fn(int $dirId, string $sigle) => Division::where('direction_id', $dirId)->where('sigle', $sigle)->firstOrFail();

        // ─── Administrateur système ───────────────────────────────────────────
        $this->creer([
            'nom' => 'ADMINISTRATEUR', 'prenom' => 'Système',
            'email' => 'admin@dgb.sn',
            'poste' => 'Administrateur Système', 'corps' => null,
            'profil' => 'CONTRACTUEL', 'matricule' => null, 'role' => 'ADMIN',
            'direction_id' => null, 'division_id' => null,
        ]);

        // ─── Valideurs centraux (DAP & DRH) ──────────────────────────────────
        // Ces deux agents valident à des niveaux 3 et 4 pour toutes les
        // demandes de décision des agents de l'État.
        $dap = $dir('DAP');
        $dgrh = $div($dap->id, 'DGRH');

        $this->creer([
            'nom' => 'DIALLO', 'prenom' => 'Ibrahima',
            'email' => 'valideur.dap@dgb.sn',
            'poste' => 'Chargé de validation DAP', 'corps' => 'Administrateur Civil',
            'profil' => 'AGENT_ETAT', 'matricule' => 'MAT-DAP-001', 'role' => 'DAP',
            'direction_id' => $dap->id, 'division_id' => $dgrh->id,
        ]);

        $this->creer([
            'nom' => 'NDIAYE', 'prenom' => 'Fatou',
            'email' => 'valideur.drh@dgb.sn',
            'poste' => 'Chargée des Ressources Humaines', 'corps' => 'Inspecteur du Travail',
            'profil' => 'AGENT_ETAT', 'matricule' => 'MAT-DRH-001', 'role' => 'DRH',
            'direction_id' => $dap->id, 'division_id' => $dgrh->id,
        ]);

        // ─── Structure par direction ──────────────────────────────────────────
        $this->seederDirection($dir, $div, 'DAP', [
            ['DGRH',  'CHEF_DIVISION', 'CISSE',    'Awa',      'Chef DGRH',              'Administrateur Civil',    'AGENT_ETAT', 'MAT-DAP-002'],
            ['DGRH',  'AGENT',         'SARR',     'Moustapha','Gestionnaire RH',         null,                      'CONTRACTUEL', null],
            ['DGRH',  'AGENT',         'BA',       'Aminata',  'Gestionnaire RH',         'Secrétaire d\'Admin.',   'AGENT_ETAT', 'MAT-DAP-003'],
            ['DMG',   'CHEF_DIVISION', 'FAYE',     'Modou',    'Chef DMG',               'Administrateur Civil',    'AGENT_ETAT', 'MAT-DAP-004'],
            ['DMG',   'AGENT',         'GUEYE',    'Ndèye',    'Gestionnaire matériel',   null,                      'CONTRACTUEL', null],
            ['DAD',   'CHEF_DIVISION', 'WADE',     'Babacar',  'Chef DAD',               'Archiviste',              'AGENT_ETAT', 'MAT-DAP-005'],
            ['BAF',   'CHEF_DIVISION', 'TOURE',    'Khadim',   'Chef BAF-DAP',           'Comptable',               'AGENT_ETAT', 'MAT-DAP-006'],
        ], 'Moussa', 'SOW', 'Directeur DAP', 'Administrateur Civil', 'AGENT_ETAT', 'MAT-DAP-000');

        $this->seederDirection($dir, $div, 'DCI', [
            ['DSD',   'CHEF_DIVISION', 'DIOP',     'Oumar',    'Chef DSD',               'Contrôleur Interne',      'AGENT_ETAT', 'MAT-DCI-001'],
            ['DSD',   'AGENT',         'LY',       'Rokhaya',  'Contrôleuse',            null,                      'CONTRACTUEL', null],
            ['DACG',  'CHEF_DIVISION', 'FALL',     'Aminata',  'Chef DACG',              'Auditeur',                'AGENT_ETAT', 'MAT-DCI-002'],
            ['DSP',   'CHEF_DIVISION', 'KANE',     'Seydou',   'Chef DSP',               'Statisticien',            'AGENT_ETAT', 'MAT-DCI-003'],
            ['BAF',   'CHEF_DIVISION', 'MBAYE',    'Aliou',    'Chef BAF-DCI',           'Comptable',               'AGENT_ETAT', 'MAT-DCI-004'],
        ], 'Pape', 'DIAW', 'Directeur DCI', 'Administrateur Civil', 'AGENT_ETAT', 'MAT-DCI-000');

        $this->seederDirection($dir, $div, 'DSI', [
            ['DED',   'CHEF_DIVISION', 'TOURE',    'Khadim',   'Chef DED',               'Ingénieur Informaticien', 'AGENT_ETAT', 'MAT-DSI-001'],
            ['DED',   'AGENT',         'NDIAYE',   'Abdou',    'Développeur',            null,                      'CONTRACTUEL', null],
            ['DED',   'AGENT',         'DIOUF',    'Cheikh',   'Développeur senior',     'Ingénieur',               'AGENT_ETAT', 'MAT-DSI-002'],
            ['DEM',   'CHEF_DIVISION', 'FALL',     'Marème',   'Chef DEM',               'Technicien Supérieur',    'AGENT_ETAT', 'MAT-DSI-003'],
            ['DEM',   'AGENT',         'DIALLO',   'Nafissatou','Technicienne réseau',   null,                      'CONTRACTUEL', null],
            ['DCQRU', 'CHEF_DIVISION', 'BADJI',    'Mamadou',  'Chef DCQRU',             'Analyste Qualité',        'AGENT_ETAT', 'MAT-DSI-004'],
            ['DCQRU', 'AGENT',         'SECK',     'Aissatou', 'Analyste',               null,                      'CONTRACTUEL', null],
            ['DPI',   'CHEF_DIVISION', 'NDOUR',    'Binta',    'Chef DPI',               'Chef de Projet',          'AGENT_ETAT', 'MAT-DSI-005'],
            ['DPI',   'AGENT',         'CISSE',    'Ibou',     'Chef de projet junior',  null,                      'CONTRACTUEL', null],
            ['BAF',   'CHEF_DIVISION', 'SOW',      'Yaye',     'Chef BAF-DSI',           'Comptable',               'AGENT_ETAT', 'MAT-DSI-006'],
        ], 'Ousmane', 'DIOP', 'Directeur DSI', 'Ingénieur Informaticien', 'AGENT_ETAT', 'MAT-DSI-000');

        $this->seederDirection($dir, $div, 'DPB', [
            ['DS',     'CHEF_DIVISION', 'THIAW',   'Mamadou',  'Chef DS-DPB',            'Économiste',              'AGENT_ETAT', 'MAT-DPB-001'],
            ['DS',     'AGENT',         'DIENE',   'Sophie',   'Analyste budgétaire',    null,                      'CONTRACTUEL', null],
            ['DSE',    'CHEF_DIVISION', 'BARRY',   'Hawa',     'Chef DSE',               'Économiste',              'AGENT_ETAT', 'MAT-DPB-002'],
            ['DSSOC',  'CHEF_DIVISION', 'MENDY',   'Robert',   'Chef DSSOC',             'Économiste',              'AGENT_ETAT', 'MAT-DPB-003'],
            ['DSSOUV', 'CHEF_DIVISION', 'GOMIS',   'Patrick',  'Chef DSSOUV',            'Économiste',              'AGENT_ETAT', 'MAT-DPB-004'],
            ['CI',     'CHEF_DIVISION', 'BASSENE', 'Jean',     'Chef CI-DPB',            'Informaticien',           'AGENT_ETAT', 'MAT-DPB-005'],
            ['BAF',    'CHEF_DIVISION', 'MANGA',   'Marie',    'Chef BAF-DPB',           'Comptable',               'AGENT_ETAT', 'MAT-DPB-006'],
        ], 'Mariama', 'BA', 'Directeur DPB', 'Économiste', 'AGENT_ETAT', 'MAT-DPB-000');

        $this->seederDirection($dir, $div, 'DCB', [
            ['DS',  'CHEF_DIVISION', 'DIATTA', 'Alphonse',  'Chef DS-DCB',  'Contrôleur Budgétaire', 'AGENT_ETAT', 'MAT-DCB-001'],
            ['DCR', 'CHEF_DIVISION', 'BADIANE','Colette',   'Chef DCR',     'Inspecteur Finance',    'AGENT_ETAT', 'MAT-DCB-002'],
            ['DCP', 'CHEF_DIVISION', 'DIOUF',  'Lamine',    'Chef DCP',     'Contrôleur',            'AGENT_ETAT', 'MAT-DCB-003'],
            ['DCP', 'AGENT',         'SAMB',   'Fatou',     'Contrôleuse',  null,                    'CONTRACTUEL', null],
            ['CI',  'CHEF_DIVISION', 'COLY',   'Edmond',    'Chef CI-DCB',  'Informaticien',         'AGENT_ETAT', 'MAT-DCB-004'],
            ['BAF', 'CHEF_DIVISION', 'DIOP',   'Ramatoulaye','Chef BAF-DCB','Comptable',             'AGENT_ETAT', 'MAT-DCB-005'],
        ], 'Seydou', 'KANE', 'Directeur DCB', 'Administrateur Civil', 'AGENT_ETAT', 'MAT-DCB-000');

        $this->seederDirection($dir, $div, 'DODP', [
            ['DODPER', 'CHEF_DIVISION', 'DIEDHIOU','Victor',   'Chef DODPER',  'Ordonnateur',           'AGENT_ETAT', 'MAT-DODP-001'],
            ['DODPER', 'AGENT',         'TENDENG', 'Clémence', 'Ordonnateur',  null,                    'CONTRACTUEL', null],
            ['DODFI',  'CHEF_DIVISION', 'SAMBOU',  'Boubacar', 'Chef DODFI',   'Ordonnateur',           'AGENT_ETAT', 'MAT-DODP-002'],
            ['DSCE',   'CHEF_DIVISION', 'FAYE',    'Bintou',   'Chef DSCE',    'Contrôleur',            'AGENT_ETAT', 'MAT-DODP-003'],
            ['BAF',    'CHEF_DIVISION', 'GOUDIABY','Issa',     'Chef BAF-DODP','Comptable',             'AGENT_ETAT', 'MAT-DODP-004'],
        ], 'Aissatou', 'SARR', 'Directeur DODP', 'Administrateur Civil', 'AGENT_ETAT', 'MAT-DODP-000');

        $this->seederDirection($dir, $div, 'DS', [
            ['DGCP', 'CHEF_DIVISION', 'DIALLO', 'Oumar',    'Chef DGCP',   'Gestionnaire Solde',    'AGENT_ETAT', 'MAT-DS-001'],
            ['DGCP', 'AGENT',         'TOURE',  'Awa',      'Liquidateur', null,                    'CONTRACTUEL', null],
            ['DTR',  'CHEF_DIVISION', 'MBAYE',  'Pape',     'Chef DTR',    'Contrôleur Solde',      'AGENT_ETAT', 'MAT-DS-002'],
            ['DRR',  'CHEF_DIVISION', 'NIANE',  'Gnagna',   'Chef DRR',    'Juriste',               'AGENT_ETAT', 'MAT-DS-003'],
            ['BAF',  'CHEF_DIVISION', 'GAYE',   'Samba',    'Chef BAF-DS', 'Comptable',             'AGENT_ETAT', 'MAT-DS-004'],
        ], 'Mamadou', 'THIAW', 'Directeur DS', 'Gestionnaire Solde', 'AGENT_ETAT', 'MAT-DS-000');

        $this->seederDirection($dir, $div, 'DP', [
            ['DPC',  'CHEF_DIVISION', 'NDIAYE', 'Coumba',   'Chef DPC',    'Gestionnaire Pensions', 'AGENT_ETAT', 'MAT-DP-001'],
            ['DPC',  'AGENT',         'FALL',   'Fatima',   'Liquidateur', null,                    'CONTRACTUEL', null],
            ['DPM',  'CHEF_DIVISION', 'KEBE',   'Aliou',    'Chef DPM',    'Gestionnaire Pensions', 'AGENT_ETAT', 'MAT-DP-002'],
            ['DRAD', 'CHEF_DIVISION', 'DIOP',   'Ndéye',    'Chef DRAD',   'Juriste',               'AGENT_ETAT', 'MAT-DP-003'],
            ['BAF',  'CHEF_DIVISION', 'SALL',   'Ibou',     'Chef BAF-DP', 'Comptable',             'AGENT_ETAT', 'MAT-DP-004'],
        ], 'Rokhaya', 'LY', 'Directeur DP', 'Administrateur Civil', 'AGENT_ETAT', 'MAT-DP-000');

        $this->seederDirection($dir, $div, 'DMTA', [
            ['DAA',  'CHEF_DIVISION', 'BADJI',  'Marcel',   'Chef DAA',    'Gestionnaire Matériel', 'AGENT_ETAT', 'MAT-DMTA-001'],
            ['DAA',  'AGENT',         'SAGNA',  'Pierre',   'Acheteur',    null,                    'CONTRACTUEL', null],
            ['DSD',  'CHEF_DIVISION', 'TENDENG','Antoine',  'Chef DSD',    'Gestionnaire Stock',    'AGENT_ETAT', 'MAT-DMTA-002'],
            ['DTP',  'CHEF_DIVISION', 'DIEDHIOU','Rosalie', 'Chef DTP',    'Gestionnaire Parc',     'AGENT_ETAT', 'MAT-DMTA-003'],
            ['BAF',  'CHEF_DIVISION', 'DIATTA', 'Emile',    'Chef BAF-DMTA','Comptable',            'AGENT_ETAT', 'MAT-DMTA-004'],
        ], 'Aliou', 'MBAYE', 'Directeur DMTA', 'Administrateur Civil', 'AGENT_ETAT', 'MAT-DMTA-000');

        $this->seederDirection($dir, $div, 'CSS', [
            ['SCAD',  'CHEF_DIVISION', 'FALL',  'Ndéye',    'Chef SCAD',   'Statisticien',          'AGENT_ETAT', 'MAT-CSS-001'],
            ['SCAD',  'AGENT',         'DIOP',  'Arame',    'Analyste',    null,                    'CONTRACTUEL', null],
            ['SRNRS', 'CHEF_DIVISION', 'SOW',   'Cheikh',   'Chef SRNRS',  'Économiste',            'AGENT_ETAT', 'MAT-CSS-002'],
            ['SSRD',  'CHEF_DIVISION', 'GUEYE', 'Adja',     'Chef SSRD',   'Gestionnaire',          'AGENT_ETAT', 'MAT-CSS-003'],
            ['BAF',   'CHEF_DIVISION', 'DIOUF', 'Awa',      'Chef BAF-CSS','Comptable',             'AGENT_ETAT', 'MAT-CSS-004'],
        ], 'Sokhna', 'DIOUF', 'Directeur CSS', 'Administrateur Civil', 'AGENT_ETAT', 'MAT-CSS-000');

        $this->seederDirection($dir, $div, 'CER', [
            ['SEEF',  'CHEF_DIVISION', 'NDIAYE','Mamadou',  'Chef SEEF',   'Économiste',            'AGENT_ETAT', 'MAT-CER-001'],
            ['SEEF',  'AGENT',         'BA',    'Mariama',  'Analyste',    null,                    'CONTRACTUEL', null],
            ['SEMJR', 'CHEF_DIVISION', 'THIAM', 'Pape',     'Chef SEMJR',  'Juriste',               'AGENT_ETAT', 'MAT-CER-002'],
            ['SEVJI', 'CHEF_DIVISION', 'DIENG', 'Adama',    'Chef SEVJI',  'Juriste',               'AGENT_ETAT', 'MAT-CER-003'],
            ['BAF',   'CHEF_DIVISION', 'NIANG', 'Cheikh',   'Chef BAF-CER','Comptable',             'AGENT_ETAT', 'MAT-CER-004'],
        ], 'Cheikh', 'NIANG', 'Directeur CER', 'Juriste', 'AGENT_ETAT', 'MAT-CER-000');
    }

    /**
     * Crée le DIRECTEUR d'une direction + tous les agents de ses divisions.
     * @param callable $dir  fn(sigle) → Direction
     * @param callable $div  fn(dir_id, sigle) → Division
     * @param string   $dirSigle
     * @param array    $agents  [ [divSigle, role, nom, prenom, poste, corps, profil, matricule], … ]
     */
    private function seederDirection(
        callable $dir, callable $div,
        string $dirSigle, array $agents,
        string $directeurPrenom, string $directeurNom,
        string $directeurPoste, string $directeurCorps,
        string $directeurProfil, string $directeurMatricule
    ): void {
        $direction  = $dir($dirSigle);
        // Premier division pour placer le directeur
        $premierDiv = Division::where('direction_id', $direction->id)->orderBy('id')->firstOrFail();

        $slug = strtolower($dirSigle);

        // Directeur
        $this->creer([
            'nom'        => $directeurNom,
            'prenom'     => $directeurPrenom,
            'email'      => "directeur.{$slug}@dgb.sn",
            'poste'      => $directeurPoste,
            'corps'      => $directeurCorps,
            'profil'     => $directeurProfil,
            'matricule'  => $directeurMatricule,
            'role'       => 'DIRECTEUR',
            'direction_id' => $direction->id,
            'division_id'  => $premierDiv->id,
        ]);

        // Agents des divisions
        $counters = [];
        foreach ($agents as [$divSigle, $role, $nom, $prenom, $poste, $corps, $profil, $matricule]) {
            $division = $div($direction->id, $divSigle);
            $divSlug  = strtolower("{$slug}.{$divSigle}");
            $counters[$divSlug] = ($counters[$divSlug] ?? 0) + 1;
            $n = $counters[$divSlug];

            $prefix = match ($role) {
                'CHEF_DIVISION' => 'chef',
                default         => "agent{$n}",
            };

            $this->creer([
                'nom'         => $nom,
                'prenom'      => $prenom,
                'email'       => "{$prefix}.{$divSlug}@dgb.sn",
                'poste'       => $poste,
                'corps'       => $corps,
                'profil'      => $profil,
                'matricule'   => $matricule,
                'role'        => $role,
                'direction_id'=> $direction->id,
                'division_id' => $division->id,
            ]);
        }
    }

    private function creer(array $data): void
    {
        Agent::firstOrCreate(
            ['email' => $data['email']],
            array_merge($data, ['password' => Hash::make('password')])
        );
    }
}

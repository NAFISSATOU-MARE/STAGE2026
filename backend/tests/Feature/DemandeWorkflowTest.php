<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\Demande;
use App\Models\Direction;
use App\Models\Division;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Couverture du workflow de validation : circuits, règles de blocage, édition de lettre.
 *
 * Rôles testés : AGENT, CHEF_DIVISION, DIRECTEUR, DGB, DRH, MINISTRE.
 * Les assertions portent sur les codes HTTP, les messages d'erreur, et la structure
 * des circuits retournés par Demande::circuit().
 */
class DemandeWorkflowTest extends TestCase
{
    use RefreshDatabase;

    // ─── Fixtures ────────────────────────────────────────────────────────────

    private Direction $direction;
    private Division  $division;

    protected function setUp(): void
    {
        parent::setUp();

        $this->direction = Direction::create(['sigle' => 'TEST', 'nom' => 'Direction Test']);
        $this->division  = Division::create([
            'direction_id' => $this->direction->id,
            'sigle'        => 'DIV1',
            'nom'          => 'Division Test 1',
        ]);
    }

    /** Crée un agent avec les attributs fournis. */
    private function creerAgent(array $attrs): Agent
    {
        return Agent::create(array_merge([
            'nom'          => 'TEST',
            'prenom'       => 'Agent',
            'email'        => uniqid('agent_') . '@test.sn',
            'password'     => bcrypt('secret'),
            'poste'        => 'Poste test',
            'profil'       => 'AGENT_ETAT',
            'role'         => 'AGENT',
            'direction_id' => $this->direction->id,
            'division_id'  => $this->division->id,
        ], $attrs));
    }

    /** Crée une décision approuvée active pour l'agent (expire dans $jours jours). */
    private function creerDecisionActive(Agent $agent, int $jours = 30): Demande
    {
        return Demande::create([
            'agent_id'        => $agent->id,
            'type'            => 'DECISION',
            'date_debut'      => now()->toDateString(),
            'date_fin'        => now()->toDateString(),
            'nombre_jours'    => 0,
            'motif'           => 'Décision test',
            'statut'          => 'APPROUVEE',
            'niveau_courant'  => 1,
            'annee'           => now()->year,
            'date_validation' => now(),
            'duree_jours'     => $jours,
        ]);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // 1. TESTS UNITAIRES — Demande::circuit()
    // ═══════════════════════════════════════════════════════════════════════

    #[Test]
    public function circuit_agent_conge_passe_par_chef_puis_directeur(): void
    {
        $agent   = $this->creerAgent(['role' => 'AGENT']);
        $demande = new Demande(['type' => 'CONGE']);
        $demande->setRelation('agent', $agent);

        $this->assertSame(['CHEF_DIVISION', 'DIRECTEUR'], $demande->circuit());
    }

    #[Test]
    public function circuit_agent_decision_passe_par_quatre_niveaux(): void
    {
        $agent   = $this->creerAgent(['role' => 'AGENT']);
        $demande = new Demande(['type' => 'DECISION']);
        $demande->setRelation('agent', $agent);

        $this->assertSame(['CHEF_DIVISION', 'DIRECTEUR', 'DAP', 'DRH'], $demande->circuit());
    }

    #[Test]
    public function circuit_chef_division_conge_va_directement_au_directeur(): void
    {
        $chef    = $this->creerAgent(['role' => 'CHEF_DIVISION']);
        $demande = new Demande(['type' => 'CONGE']);
        $demande->setRelation('agent', $chef);

        $this->assertSame(['DIRECTEUR'], $demande->circuit());
    }

    #[Test]
    public function circuit_directeur_conge_va_au_dgb_uniquement(): void
    {
        $dir     = $this->creerAgent(['role' => 'DIRECTEUR', 'division_id' => null]);
        $demande = new Demande(['type' => 'CONGE']);
        $demande->setRelation('agent', $dir);

        $this->assertSame(['DGB'], $demande->circuit());
    }

    #[Test]
    public function circuit_directeur_decision_passe_par_dgb_puis_drh(): void
    {
        $dir     = $this->creerAgent(['role' => 'DIRECTEUR', 'division_id' => null]);
        $demande = new Demande(['type' => 'DECISION']);
        $demande->setRelation('agent', $dir);

        $this->assertSame(['DGB', 'DRH'], $demande->circuit());
    }

    #[Test]
    public function circuit_dgb_va_au_ministre_pour_tout_type(): void
    {
        $dgb = $this->creerAgent(['role' => 'DGB', 'direction_id' => null, 'division_id' => null]);

        foreach (['CONGE', 'DECISION'] as $type) {
            $demande = new Demande(['type' => $type]);
            $demande->setRelation('agent', $dgb);
            $this->assertSame(['MINISTRE'], $demande->circuit(), "Circuit DGB {$type} incorrect.");
        }
    }

    #[Test]
    public function circuit_drh_conge_va_directement_au_dgb(): void
    {
        $drh     = $this->creerAgent(['role' => 'DRH', 'direction_id' => null, 'division_id' => null]);
        $demande = new Demande(['type' => 'CONGE']);
        $demande->setRelation('agent', $drh);

        $this->assertSame(['DGB'], $demande->circuit());
    }

    // ═══════════════════════════════════════════════════════════════════════
    // 2. RÈGLES DE BLOCAGE — POST /api/demandes
    // ═══════════════════════════════════════════════════════════════════════

    #[Test]
    public function ministre_ne_peut_pas_soumettre_de_demande(): void
    {
        $ministre = $this->creerAgent([
            'role'         => 'MINISTRE',
            'direction_id' => null,
            'division_id'  => null,
        ]);

        foreach (['CONGE', 'DECISION'] as $type) {
            $payload = $type === 'CONGE'
                ? ['type' => 'CONGE', 'date_debut' => now()->addDay()->toDateString(),
                   'date_fin' => now()->addDays(5)->toDateString(), 'motif' => 'test']
                : ['type' => 'DECISION', 'motif' => 'test'];

            $this->actingAs($ministre)
                 ->postJson('/api/demandes', $payload)
                 ->assertStatus(403)
                 ->assertJsonFragment(['message' => 'Le Ministre ne peut pas soumettre de demande.']);
        }
    }

    #[Test]
    public function drh_ne_peut_pas_soumettre_une_decision(): void
    {
        $drh = $this->creerAgent(['role' => 'DRH', 'direction_id' => null, 'division_id' => null]);

        $this->actingAs($drh)
             ->postJson('/api/demandes', ['type' => 'DECISION', 'motif' => 'test'])
             ->assertStatus(422)
             ->assertJsonFragment(['message' => 'Le DRH ne peut pas soumettre de demande de décision.']);
    }

    #[Test]
    public function ne_peut_pas_soumettre_une_decision_si_decision_active_existe(): void
    {
        $agent = $this->creerAgent(['role' => 'AGENT']);
        $this->creerDecisionActive($agent);

        $this->actingAs($agent)
             ->postJson('/api/demandes', ['type' => 'DECISION', 'motif' => 'Nouvelle décision'])
             ->assertStatus(422)
             ->assertJsonFragment(['message' => 'Vous avez déjà une décision en cours de validité. Attendez son expiration avant d\'en soumettre une nouvelle.']);
    }

    #[Test]
    public function peut_soumettre_une_decision_si_aucune_decision_active(): void
    {
        $agent = $this->creerAgent(['role' => 'AGENT']);

        $this->actingAs($agent)
             ->postJson('/api/demandes', ['type' => 'DECISION', 'motif' => 'Première décision'])
             ->assertStatus(201)
             ->assertJsonFragment(['type' => 'DECISION', 'statut' => 'EN_ATTENTE']);
    }

    #[Test]
    public function peut_soumettre_une_decision_si_ancienne_decision_expiree(): void
    {
        $agent = $this->creerAgent(['role' => 'AGENT']);
        // Décision expirée : date_validation il y a 60 jours, durée 30 jours
        Demande::create([
            'agent_id'        => $agent->id,
            'type'            => 'DECISION',
            'date_debut'      => now()->subDays(60)->toDateString(),
            'date_fin'        => now()->subDays(60)->toDateString(),
            'nombre_jours'    => 0,
            'motif'           => 'Décision expirée',
            'statut'          => 'APPROUVEE',
            'niveau_courant'  => 1,
            'annee'           => now()->year,
            'date_validation' => now()->subDays(60),
            'duree_jours'     => 30,
        ]);

        $this->actingAs($agent)
             ->postJson('/api/demandes', ['type' => 'DECISION', 'motif' => 'Nouvelle demande'])
             ->assertStatus(201);
    }

    #[Test]
    public function conge_refuse_sans_decision_active_pour_agent_etat(): void
    {
        $agent = $this->creerAgent(['role' => 'AGENT', 'profil' => 'AGENT_ETAT']);

        $this->actingAs($agent)
             ->postJson('/api/demandes', [
                 'type'       => 'CONGE',
                 'date_debut' => now()->addDay()->toDateString(),
                 'date_fin'   => now()->addDays(5)->toDateString(),
                 'motif'      => 'Congé annuel',
             ])
             ->assertStatus(422)
             ->assertJsonFragment(['message' => 'Vous devez avoir une décision active pour soumettre une demande de congé.']);
    }

    #[Test]
    public function conge_accepte_avec_decision_active_pour_agent_etat(): void
    {
        $agent = $this->creerAgent(['role' => 'AGENT', 'profil' => 'AGENT_ETAT']);
        $this->creerDecisionActive($agent);

        $this->actingAs($agent)
             ->postJson('/api/demandes', [
                 'type'       => 'CONGE',
                 'date_debut' => now()->addDay()->toDateString(),
                 'date_fin'   => now()->addDays(5)->toDateString(),
                 'motif'      => 'Congé annuel',
             ])
             ->assertStatus(201)
             ->assertJsonFragment(['type' => 'CONGE']);
    }

    #[Test]
    public function drh_peut_soumettre_un_conge_sans_decision_active(): void
    {
        $drh = $this->creerAgent([
            'role'         => 'DRH',
            'profil'       => 'AGENT_ETAT',
            'direction_id' => null,
            'division_id'  => null,
        ]);

        // Aucune décision active — le DRH doit quand même pouvoir soumettre un congé.
        $this->actingAs($drh)
             ->postJson('/api/demandes', [
                 'type'       => 'CONGE',
                 'date_debut' => now()->addDay()->toDateString(),
                 'date_fin'   => now()->addDays(3)->toDateString(),
                 'motif'      => 'Congé DRH',
             ])
             ->assertStatus(201)
             ->assertJsonFragment(['type' => 'CONGE']);
    }

    #[Test]
    public function dgb_conge_bloque_sans_decision_active(): void
    {
        $dgb = $this->creerAgent([
            'role'         => 'DGB',
            'profil'       => 'AGENT_ETAT',
            'direction_id' => null,
            'division_id'  => null,
        ]);

        // Le DGB n'est PAS exempté de l'obligation d'avoir une décision active.
        $this->actingAs($dgb)
             ->postJson('/api/demandes', [
                 'type'       => 'CONGE',
                 'date_debut' => now()->addDay()->toDateString(),
                 'date_fin'   => now()->addDays(3)->toDateString(),
                 'motif'      => 'Congé DGB',
             ])
             ->assertStatus(422)
             ->assertJsonFragment(['message' => 'Vous devez avoir une décision active pour soumettre une demande de congé.']);
    }

    #[Test]
    public function contractuel_peut_faire_un_conge_sans_decision(): void
    {
        $agent = $this->creerAgent(['role' => 'AGENT', 'profil' => 'CONTRACTUEL']);

        $this->actingAs($agent)
             ->postJson('/api/demandes', [
                 'type'       => 'CONGE',
                 'date_debut' => now()->addDay()->toDateString(),
                 'date_fin'   => now()->addDays(5)->toDateString(),
                 'motif'      => 'Congé contractuel',
             ])
             ->assertStatus(201);
    }

    #[Test]
    public function contractuel_ne_peut_pas_faire_une_decision(): void
    {
        $agent = $this->creerAgent(['role' => 'AGENT', 'profil' => 'CONTRACTUEL']);

        $this->actingAs($agent)
             ->postJson('/api/demandes', ['type' => 'DECISION', 'motif' => 'test'])
             ->assertStatus(422);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // 3. FILE D'ATTENTE DU DGB (pending)
    // ═══════════════════════════════════════════════════════════════════════

    #[Test]
    public function dgb_voit_les_conges_du_drh_en_attente(): void
    {
        $dgb = $this->creerAgent([
            'role' => 'DGB', 'profil' => 'AGENT_ETAT',
            'direction_id' => null, 'division_id' => null,
        ]);
        $drh = $this->creerAgent([
            'role' => 'DRH', 'profil' => 'AGENT_ETAT',
            'direction_id' => null, 'division_id' => null,
        ]);

        Demande::create([
            'agent_id' => $drh->id, 'type' => 'CONGE',
            'date_debut' => now()->addDay()->toDateString(),
            'date_fin' => now()->addDays(5)->toDateString(),
            'nombre_jours' => 5, 'motif' => 'Congé DRH',
            'statut' => 'EN_ATTENTE', 'niveau_courant' => 1,
            'annee' => now()->year,
        ]);

        $response = $this->actingAs($dgb)
                         ->getJson('/api/validations/pending')
                         ->assertOk();

        $this->assertCount(1, $response->json());
        $this->assertSame('DRH', $response->json('0.agent.role'));
    }

    #[Test]
    public function dgb_voit_aussi_les_demandes_du_directeur_en_attente(): void
    {
        $dgb = $this->creerAgent([
            'role' => 'DGB', 'profil' => 'AGENT_ETAT',
            'direction_id' => null, 'division_id' => null,
        ]);
        $directeur = $this->creerAgent(['role' => 'DIRECTEUR']);

        Demande::create([
            'agent_id' => $directeur->id, 'type' => 'CONGE',
            'date_debut' => now()->addDay()->toDateString(),
            'date_fin' => now()->addDays(3)->toDateString(),
            'nombre_jours' => 3, 'motif' => 'Congé directeur',
            'statut' => 'EN_ATTENTE', 'niveau_courant' => 1,
            'annee' => now()->year,
        ]);

        $response = $this->actingAs($dgb)
                         ->getJson('/api/validations/pending')
                         ->assertOk();

        $this->assertCount(1, $response->json());
        $this->assertSame('DIRECTEUR', $response->json('0.agent.role'));
    }

    // ═══════════════════════════════════════════════════════════════════════
    // 4. GÉNÉRATION ET ÉDITION DE LETTRE
    // ═══════════════════════════════════════════════════════════════════════

    #[Test]
    public function proprietaire_peut_editer_le_contenu_de_la_lettre(): void
    {
        $agent = $this->creerAgent(['role' => 'AGENT', 'profil' => 'CONTRACTUEL']);
        $demande = Demande::create([
            'agent_id' => $agent->id, 'type' => 'CONGE',
            'date_debut' => now()->addDay()->toDateString(),
            'date_fin' => now()->addDays(5)->toDateString(),
            'nombre_jours' => 5, 'motif' => 'Congé',
            'statut' => 'EN_ATTENTE', 'niveau_courant' => 1,
            'annee' => now()->year,
        ]);

        $this->actingAs($agent)
             ->putJson("/api/demandes/{$demande->id}/lettre", [
                 'motif_lettre'    => 'Départ pour raisons personnelles.',
                 'lieu_jouissance' => 'Saint-Louis',
                 'complement'      => 'Merci de bien vouloir agréer…',
             ])
             ->assertOk()
             ->assertJsonFragment(['message' => 'Contenu de la lettre mis à jour.']);

        $this->assertDatabaseHas('demandes', [
            'id' => $demande->id,
        ]);
        $demande->refresh();
        $this->assertSame('Saint-Louis', $demande->contenu_lettre['lieu_jouissance']);
    }

    #[Test]
    public function tiers_ne_peut_pas_editer_la_lettre_dautrui(): void
    {
        $proprietaire = $this->creerAgent(['role' => 'AGENT', 'profil' => 'CONTRACTUEL']);
        $autreAgent   = $this->creerAgent(['role' => 'AGENT', 'profil' => 'CONTRACTUEL',
                                           'email' => 'autre@test.sn']);
        $demande = Demande::create([
            'agent_id' => $proprietaire->id, 'type' => 'CONGE',
            'date_debut' => now()->addDay()->toDateString(),
            'date_fin' => now()->addDays(5)->toDateString(),
            'nombre_jours' => 5, 'motif' => 'Congé',
            'statut' => 'EN_ATTENTE', 'niveau_courant' => 1,
            'annee' => now()->year,
        ]);

        $this->actingAs($autreAgent)
             ->putJson("/api/demandes/{$demande->id}/lettre", [
                 'motif_lettre' => 'Tentative de modification.',
             ])
             ->assertStatus(403);
    }

    #[Test]
    public function edition_lettre_refusee_pour_une_decision(): void
    {
        $agent = $this->creerAgent(['role' => 'AGENT', 'profil' => 'AGENT_ETAT']);
        $demande = Demande::create([
            'agent_id' => $agent->id, 'type' => 'DECISION',
            'date_debut' => now()->toDateString(), 'date_fin' => now()->toDateString(),
            'nombre_jours' => 0, 'motif' => 'Décision',
            'statut' => 'EN_ATTENTE', 'niveau_courant' => 1,
            'annee' => now()->year,
        ]);

        $this->actingAs($agent)
             ->putJson("/api/demandes/{$demande->id}/lettre", [
                 'motif_lettre' => 'test',
             ])
             ->assertStatus(422)
             ->assertJsonFragment(['message' => 'La génération de lettre est réservée aux demandes de congé.']);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // 5. Agent::peutSoumettreConge()
    // ═══════════════════════════════════════════════════════════════════════

    #[Test]
    public function peut_soumettre_conge_retourne_false_sans_decision_active(): void
    {
        $agent = $this->creerAgent(['role' => 'AGENT', 'profil' => 'AGENT_ETAT']);
        $this->assertFalse($agent->peutSoumettreConge());
    }

    #[Test]
    public function peut_soumettre_conge_retourne_true_avec_decision_active(): void
    {
        $agent = $this->creerAgent(['role' => 'AGENT', 'profil' => 'AGENT_ETAT']);
        $this->creerDecisionActive($agent);
        $this->assertTrue($agent->peutSoumettreConge());
    }

    #[Test]
    public function drh_peut_soumettre_conge_sans_decision_active(): void
    {
        $drh = $this->creerAgent([
            'role' => 'DRH', 'profil' => 'AGENT_ETAT',
            'direction_id' => null, 'division_id' => null,
        ]);
        $this->assertTrue($drh->peutSoumettreConge());
    }

    #[Test]
    public function dgb_ne_peut_pas_soumettre_conge_sans_decision_active(): void
    {
        $dgb = $this->creerAgent([
            'role' => 'DGB', 'profil' => 'AGENT_ETAT',
            'direction_id' => null, 'division_id' => null,
        ]);
        $this->assertFalse($dgb->peutSoumettreConge());
    }

    #[Test]
    public function decision_active_expiree_ne_compte_pas(): void
    {
        $agent = $this->creerAgent(['role' => 'AGENT', 'profil' => 'AGENT_ETAT']);
        Demande::create([
            'agent_id'        => $agent->id,
            'type'            => 'DECISION',
            'date_debut'      => now()->subDays(60)->toDateString(),
            'date_fin'        => now()->subDays(60)->toDateString(),
            'nombre_jours'    => 0,
            'motif'           => 'Expirée',
            'statut'          => 'APPROUVEE',
            'niveau_courant'  => 1,
            'annee'           => now()->subDays(60)->year,
            'date_validation' => now()->subDays(60),
            'duree_jours'     => 30,   // expirée il y a 30 jours
        ]);
        $this->assertFalse($agent->peutSoumettreConge());
        $this->assertNull($agent->decisionActive());
    }
}

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body {
    font-family: Arial, Helvetica, sans-serif;
    font-size: 11px;
    color: #000;
    padding: 25px 35px;
  }

  /* ── En-tête ── */
  .header-table { width: 100%; margin-bottom: 18px; }
  .header-left  { width: 65%; vertical-align: top; }
  .header-right { width: 35%; vertical-align: top; text-align: right; }
  .republic     { font-size: 10px; font-weight: bold; line-height: 1.6; }
  .ministry     { font-size: 11px; font-weight: bold; line-height: 1.6; margin-top: 4px; }
  .dgb-brand    { font-size: 15px; font-weight: bold; border: 2px solid #000;
                  display: inline-block; padding: 2px 6px; margin: 6px 0 2px; }
  .dgb-sub      { font-size: 9px; }
  .direction-name { font-size: 11px; font-weight: bold; margin-top: 6px; }
  .date-line    { font-size: 11px; margin-top: 8px; }

  /* ── Séparateur ── */
  hr { border: none; border-top: 1px solid #000; margin: 10px 0; }

  /* ── Titre ── */
  .title {
    text-align: center;
    font-size: 13px;
    font-weight: bold;
    text-transform: uppercase;
    text-decoration: underline;
    margin: 14px 0 18px;
    letter-spacing: 0.5px;
  }

  /* ── Champs formulaire ── */
  .field { margin-bottom: 8px; line-height: 1.6; }
  .field-line {
    display: inline-block;
    border-bottom: 1px dotted #555;
    min-width: 260px;
    margin-left: 4px;
  }
  .field-inline { display: inline-block; margin-right: 30px; }
  .checkbox-group { margin-bottom: 8px; }
  .cb { display: inline-block; width: 12px; height: 12px; border: 1px solid #000;
        text-align: center; line-height: 11px; font-size: 10px; margin: 0 4px; vertical-align: middle; }

  /* ── Section deux colonnes ── */
  .two-col { width: 100%; margin-top: 6px; }
  .col-left { width: 55%; vertical-align: top; }
  .col-right { width: 45%; vertical-align: top; }

  /* ── Zones de jours déductibles ── */
  .jours-block {
    border: 1px solid #aaa;
    padding: 6px 10px;
    margin: 8px 0;
    background: #fafafa;
    font-size: 10px;
  }

  /* ── Signatures ── */
  .sig-section {
    border-top: 1.5px solid #000;
    margin-top: 22px;
    padding-top: 10px;
  }
  .sig-table { width: 100%; margin-top: 10px; }
  .sig-left  { width: 55%; vertical-align: top; }
  .sig-right { width: 45%; vertical-align: top; }
  .sig-box {
    border: 1px solid #aaa;
    min-height: 55px;
    padding: 6px 8px;
    margin-top: 4px;
    font-size: 10px;
  }
  .sig-title { font-weight: bold; font-size: 11px; text-align: center; margin-bottom: 6px; }

  /* ── Badge statut ── */
  .badge {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 3px;
    font-size: 10px;
    font-weight: bold;
  }
  .badge-attente  { border: 1px solid #888; color: #555; }
  .badge-approuve { border: 1px solid #2a7; color: #2a7; }
  .badge-rejete   { border: 1px solid #c33; color: #c33; }

  /* ── Historique validations ── */
  .hist-table { width: 100%; border-collapse: collapse; margin-top: 8px; font-size: 10px; }
  .hist-table th, .hist-table td {
    border: 1px solid #ccc; padding: 4px 8px; text-align: left;
  }
  .hist-table th { background: #eee; font-weight: bold; }
  .favorable   { color: #2a7; font-weight: bold; }
  .defavorable { color: #c33; font-weight: bold; }

  .page-note { font-size: 9px; color: #777; text-align: center; margin-top: 18px; }
</style>
</head>
<body>

{{-- ══════════════════════════════════════════════════════════
     EN-TÊTE INSTITUTIONNEL
══════════════════════════════════════════════════════════ --}}
<table class="header-table">
  <tr>
    <td class="header-left">
      <div style="display:flex;align-items:flex-start;gap:10px;">
        @if(!empty($logoSrc))
          <img src="{{ $logoSrc }}" alt="Logo DGB"
               style="height:64px;width:auto;flex-shrink:0;" />
        @endif
        <div>
          <div class="republic">
            REPUBLIQUE DU SENEGAL<br>
            <span style="font-weight:normal;font-style:italic;">Un Peuple – Un But – Une Foi</span>
          </div>
          <div class="ministry">
            MINISTERE DE L'ECONOMIE,<br>DES FINANCES ET DU PLAN
          </div>
          <div style="margin-top:4px;">
            <span class="dgb-brand">&#9673;DGB</span><br>
            <span class="dgb-sub">Direction générale du Budget</span>
          </div>
          <div class="direction-name">{{ $agent->direction->nom }}</div>
        </div>
      </div>
    </td>
    <td class="header-right">
      <div class="date-line">
        Dakar, le {{ \Carbon\Carbon::parse($demande->created_at)->translatedFormat('d F Y') }}
      </div>
      @if($demande->numero_reference)
        <div style="margin-top:8px;font-size:10px;">
          Réf. : <strong>{{ $demande->numero_reference }}</strong>
        </div>
      @endif
    </td>
  </tr>
</table>

<hr>

{{-- ══════════════════════════════════════════════════════════
     TITRE
══════════════════════════════════════════════════════════ --}}
<div class="title">Demande de Jouissance de Congé Annuel</div>

{{-- ══════════════════════════════════════════════════════════
     INFORMATIONS DE L'AGENT
══════════════════════════════════════════════════════════ --}}
<div class="field">
  Prénoms et Nom :
  <span class="field-line">{{ $agent->prenom }} {{ $agent->nom }}</span>
</div>

<div class="field">
  N° matricule :
  <span class="field-line">{{ $agent->matricule ?? '—' }}</span>
</div>

<div class="field">
  Corps :
  <span class="field-line">{{ $agent->corps ?? '—' }}</span>
</div>

<div class="checkbox-group">
  Statut :
  <span class="cb">{{ $agent->profil === 'AGENT_ETAT' ? '✓' : '' }}</span> fonctionnaire
  &nbsp;&nbsp;&nbsp;
  <span class="cb">{{ $agent->profil === 'CONTRACTUEL' ? '✓' : '' }}</span> non fonctionnaire
</div>

<div class="field">
  Fonction occupée :
  <span class="field-line">{{ $agent->poste }}</span>
</div>

<div class="field">
  Service :
  <span class="field-line">{{ $agent->division->nom }} — {{ $agent->direction->sigle }}</span>
</div>

<div style="height:6px;"></div>

{{-- ══════════════════════════════════════════════════════════
     DÉTAILS DE LA DEMANDE
══════════════════════════════════════════════════════════ --}}
<div class="field">
  Sollicite un congé annuel de
  <span class="field-line" style="min-width:40px;text-align:center;"><strong>{{ $demande->nombre_jours }}</strong></span>
  jour(s)
</div>

<div class="field">
  Date de départ en congé sollicitée :
  <span class="field-line">{{ \Carbon\Carbon::parse($demande->date_debut)->translatedFormat('d F Y') }}</span>
</div>

<div class="field">
  Lieu de jouissance du congé :
  <span class="field-line">{{ $lettre['lieu_jouissance'] ?? $demande->lieu_jouissance ?? '—' }}</span>
</div>

{{-- Motif personnalisé (éditable par l'agent avant impression) --}}
@if(!empty($lettre['motif_lettre']))
<div class="field" style="margin-top:6px;">
  Motif :
  <span class="field-line" style="min-width:350px;">{{ $lettre['motif_lettre'] }}</span>
</div>
@endif

{{-- Référence et jours déductibles --}}
<div class="jours-block">
  <div class="field-inline">
    Référence de la décision accordant le dernier congé :
    <strong>{{ $derniereDemande?->numero_reference ?? '—' }}</strong>
  </div>
  <br>
  Nombres de jours de congés ou de permissions déductibles accordés depuis la dernière décision de congé :
  <strong>{{ $joursDepuisDerniere > 0 ? $joursDepuisDerniere . ' jour(s)' : '—' }}</strong>
</div>

{{-- Date de la demande + signature agent --}}
<table class="two-col" style="margin-top:14px;">
  <tr>
    <td class="col-left">
      Date de la demande : <strong>{{ \Carbon\Carbon::parse($demande->created_at)->translatedFormat('d F Y') }}</strong>
    </td>
    <td class="col-right">
      Signature de l'agent :<br>
      <div style="border-bottom:1px solid #000;min-height:30px;margin-top:4px;"></div>
    </td>
  </tr>
</table>

{{-- ══════════════════════════════════════════════════════════
     SECTION VALIDATION (avis hiérarchique)
══════════════════════════════════════════════════════════ --}}
<div class="sig-section">

  @php
    $valNiv1 = $demande->validations->firstWhere('niveau', 1);
    $valNiv2 = $demande->validations->firstWhere('niveau', 2);
  @endphp

  <div class="field">
    Avis du supérieur hiérarchique :
    @if($valNiv1)
      <span class="{{ $valNiv1->avis === 'FAVORABLE' ? 'favorable' : 'defavorable' }}">
        {{ $valNiv1->avis === 'FAVORABLE' ? 'Favorable' : 'Défavorable' }}
      </span>
      ({{ $valNiv1->valideur->prenom }} {{ $valNiv1->valideur->nom }})
    @else
      <span class="field-line" style="min-width:200px;"></span>
    @endif
  </div>

  <div class="field">
    Motif :
    <span class="field-line" style="min-width:350px;">
      {{ $valNiv1?->motif_refus ?? '' }}
    </span>
  </div>

  <div style="height:8px;"></div>

  <table class="sig-table">
    <tr>
      {{-- Colonne gauche : Décision Chef de service --}}
      <td class="sig-left">
        <table style="width:100%;">
          <tr>
            <td style="vertical-align:top; padding-right:8px;">
              Décision du Chef de service :
              @if($valNiv1)
                <span class="{{ $valNiv1->avis === 'FAVORABLE' ? 'favorable' : 'defavorable' }}">
                  {{ $valNiv1->avis === 'FAVORABLE' ? 'Accordé' : 'Refusé' }}
                </span>
              @else
                <span class="field-line" style="min-width:70px;"></span>
              @endif
            </td>
            <td style="vertical-align:top;">
              Nombre de jours de congés :
              <strong>{{ $demande->nombre_jours }}</strong><br><br>
              Date de départ :
              <strong>{{ \Carbon\Carbon::parse($demande->date_debut)->format('d/m/Y') }}</strong><br><br>
              Date de reprise :
              <strong>{{ $dateReprise }}</strong>
            </td>
          </tr>
        </table>
      </td>

      {{-- Colonne droite : Signature du Directeur --}}
      <td class="sig-right">
        <div class="sig-title">Signature du Directeur</div>
        <div class="sig-box">
          @if($valNiv2)
            <div class="{{ $valNiv2->avis === 'FAVORABLE' ? 'favorable' : 'defavorable' }}">
              {{ $valNiv2->avis === 'FAVORABLE' ? 'Approuvé' : 'Refusé' }}
            </div>
            <div style="margin-top:4px;">{{ $valNiv2->valideur->prenom }} {{ $valNiv2->valideur->nom }}</div>
            @if($valNiv2->motif_refus)
              <div style="margin-top:4px;font-style:italic;">{{ $valNiv2->motif_refus }}</div>
            @endif
          @endif
        </div>
      </td>
    </tr>
  </table>

  {{-- Pour les demandes de décision (4 niveaux), afficher les avis DAP et DRH --}}
  @if($demande->type === 'DECISION')
    @php
      $valNiv3 = $demande->validations->firstWhere('niveau', 3);
      $valNiv4 = $demande->validations->firstWhere('niveau', 4);
    @endphp
    @if($valNiv3 || $valNiv4)
      <div style="margin-top:14px; border-top:1px dashed #aaa; padding-top:8px;">
        <div style="font-weight:bold; margin-bottom:6px; font-size:10px;">
          Avis complémentaires (Demande de Décision)
        </div>
        <table class="hist-table">
          <thead>
            <tr>
              <th>Niveau</th><th>Valideur</th><th>Avis</th><th>Motif de refus</th><th>Date</th>
            </tr>
          </thead>
          <tbody>
            @foreach($demande->validations->whereIn('niveau', [3,4]) as $v)
            <tr>
              <td>Niveau {{ $v->niveau }} ({{ $v->niveau === 3 ? 'DAP' : 'DRH' }})</td>
              <td>{{ $v->valideur->prenom }} {{ $v->valideur->nom }}</td>
              <td class="{{ $v->avis === 'FAVORABLE' ? 'favorable' : 'defavorable' }}">
                {{ $v->avis === 'FAVORABLE' ? 'Favorable' : 'Défavorable' }}
              </td>
              <td>{{ $v->motif_refus ?? '—' }}</td>
              <td>{{ $v->created_at->format('d/m/Y') }}</td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
  @endif

</div>

{{-- Statut final --}}
<div style="text-align:right; margin-top:12px;">
  @php
    $badgeClass = match($demande->statut) {
      'APPROUVEE' => 'badge-approuve',
      'REJETEE'   => 'badge-rejete',
      default     => 'badge-attente',
    };
    $badgeLabel = match($demande->statut) {
      'APPROUVEE' => 'Approuvée',
      'REJETEE'   => 'Rejetée',
      default     => 'En attente',
    };
  @endphp
  <span class="badge {{ $badgeClass }}">{{ $badgeLabel }}</span>
</div>

@if(!empty($lettre['complement']))
<div style="margin-top:12px; font-size:10px; font-style:italic; border-top:1px dashed #ccc; padding-top:6px;">
  {{ $lettre['complement'] }}
</div>
@endif

<div class="page-note">
  Document généré par le Système de Gestion des Congés — DGB &nbsp;|&nbsp;
  Réf. : {{ $demande->numero_reference ?? 'En cours' }}
</div>

</body>
</html>

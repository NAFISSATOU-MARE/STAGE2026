# STAGE2026 — Application de gestion des congés et des décisions (DGB)

Application web permettant la dématérialisation des demandes de **congé** (agents
contractuels) et de **décision** (agents de l'État) au sein de la **Direction
Générale du Budget (DGB)**, avec circuit de validation hiérarchique et suivi
de l'historique des demandes.

## Sommaire

- [Contexte](#contexte)
- [Fonctionnalités principales](#fonctionnalités-principales)
- [Stack technique](#stack-technique)
- [Structure du dépôt](#structure-du-dépôt)
- [Installation](#installation)
- [Organisation de la DGB](#organisation-de-la-dgb)
- [Statut du projet](#statut-du-projet)

## Contexte

La DGB est organisée en directions et divisions. Chaque agent (contractuel ou
agent de l'État) peut soumettre une demande de congé ou de décision, qui suit
un circuit de validation hiérarchique avant d'être acceptée ou rejetée.

## Fonctionnalités principales

- Authentification et tableau de bord par profil (agent / valideur)
- Formulaire de demande pré-rempli (nom, prénom, division)
- Circuit de validation :
  - **Agent contractuel** (demande de congé) : Chef de Division → Directeur
  - **Agent de l'État** (demande de décision) : Chef de Division → Directeur → DAP → DRH
- Motif obligatoire à chaque avis défavorable
- Calcul automatique du solde de jours disponibles (30 j/an, cumulable sur 2
  ans, 90 j maximum)
- Historique complet des demandes (année en cours et années précédentes)
- Référentiel des directions et divisions de la DGB

## Stack technique

| Composant      | Technologie                          |
|-----------------|---------------------------------------|
| Backend / API   | PHP, Laravel, Laravel Sanctum        |
| Frontend        | React (Vite), React Router, Axios    |
| Base de données | MySQL (production) / SQLite (dev)    |

## Structure du dépôt

```
STAGE2026/
├── backend/      # API Laravel
├── frontend/     # Application React
├── docs/         # Documentation, diagrammes UML, cahier des charges
└── README.md
```

## Installation

### Backend (Laravel)

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

### Frontend (React)

```bash
cd frontend
npm install
npm run dev
```

## Organisation de la DGB

La DGB comprend 11 directions/cellules, chacune subdivisée en divisions ou
sections. Le détail complet est disponible dans `docs/` et repris dans les
seeders Laravel (`backend/database/seeders/`).

| Sigle | Direction / Cellule                                   |
|-------|--------------------------------------------------------|
| DAP   | Direction de l'Administration et du Personnel          |
| DCI   | Direction du Contrôle Interne                          |
| DSI   | Direction des Systèmes d'Information                   |
| DPB   | Direction de la Programmation Budgétaire               |
| DCB   | Direction du Contrôle Budgétaire                        |
| DODP  | Direction de l'Ordonnancement des Dépenses Publiques    |
| DS    | Direction de la Solde                                   |
| DP    | Direction des Pensions                                  |
| DMTA  | Direction du Matériel et du Transit Administratif       |
| CSS   | Cellule de Suivi et de Synthèse                         |
| CER   | Cellule des Études et de la Réglementation              |

## Statut du projet

🚧 Projet en cours de développement (stage 2026).

- [ ] Architecture des modèles
- [ ] Migrations et seeders
- [ ] Authentification (Sanctum)
- [ ] Workflow de validation (congés / décisions)
- [ ] Frontend React
- [ ] Tests
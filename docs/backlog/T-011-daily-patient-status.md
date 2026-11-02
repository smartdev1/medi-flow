---
id: T-011
title: État quotidien des patients
status: todo
owner: fullstack-devops
author_of_code:
priority: P1
size: M
---

## Context
Les responsables (docteur_general, admin, accueil) ont besoin d'une vue consolidée des patients vus dans la journée : qui est arrivé, qui est reparti, quel service a été consulté, quel médecin a reçu le patient.

Aujourd'hui, chaque service a son propre tableau (accueil, hospitalisation, laboratoire, pharmacie) mais il n'y a pas de vue transversale.

## User story
As a responsable, I consult the list of all patients seen today with their arrival/departure times, service, doctor, and status, so that I have a daily overview of clinic activity.

## Acceptance criteria

### AC1 — Route
- [ ] New route: GET `/etat-quotidien` — name `etat-quotidien.index`
- [ ] Route protected by `role:accueil,docteur_general,admin,aide_soignante`
- [ ] Accessible depuis la sidebar (sous la section "Supervision" ou équivalent)

### AC2 — Controller — EtatQuotidienController
- [ ] New controller `App\Http\Controllers\EtatQuotidienController`
- [ ] `index()` method:
  - Accepts query params: `date` (default: today), `service` (filter), `medecin` (filter), `statut` (filter), `search` (patient name/code)
  - Query builds from `Visit` model:
    - Joins patient, act, admissions
    - Filters by date range on `arrived_at`
    - Filters by service/category if provided (via `act.category`)
    - Filters by doctor if provided (via `act?->category` or a dedicated field — see technical notes)
    - Filters by status if provided
    - Search by patient name or code
  - Orders by `arrived_at` ascending
  - Passes data to view

### AC3 — Colonnes attendues
- [ ] **Patient** : nom complet + code patient
- [ ] **Service** : catégorie de l'acte (Consultation, Chirurgie, Laboratoire, etc.) — issu de `visit->act->category`
- [ ] **Médecin** : nom du médecin ayant pris en charge — issu de `visit->act?->category` (à confirmer, voir notes techniques)
- [ ] **Heure d'arrivée** : `visit->arrived_at` formaté HH:MM
- [ ] **Heure de départ** : `visit->discharged_at` formaté HH:MM, ou `—` si encore présent
- [ ] **Statut** : statut de la visite avec badge coloré

### AC4 — Filtres
- [ ] Filtre **date** : date picker, défaut = aujourd'hui
- [ ] Filtre **service** : dropdown avec les catégories d'actes disponibles (distinctes)
- [ ] Filtre **statut** : dropdown avec les statuts de visite pertinents (En attente, En consultation, Consult. terminée, Hospitalisé, etc.)
- [ ] Filtre **recherche** : texte, recherche par nom ou code patient
- [ ] Les filtres se combinent (AND)
- [ ] Les filtres sont affichés dans l'URL query string (pas de session)

### AC5 — Vue
- [ ] `resources/views/etat-quotidien/index.blade.php`
- [ ] Header avec titre "État quotidien" et date sélectionnée
- [ ] Ligne de filtres horizontale
- [ ] Tableau responsive avec les colonnes AC3
- [ ] Pagination si plus de 50 résultats
- [ ] Message "Aucun patient pour cette période" si vide
- [ ] Même layout/app-shell que les autres écrans

### AC6 — Export (optionnel — à confirmer avec l'utilisateur)
- [ ] Bouton **Export CSV** (implémenté)
- [ ] Export contient les mêmes colonnes que le tableau
- [ ] Bouton **Export PDF** (ajourné — nécessite une bibliothèque d'export PDF)

### AC7 — Performance
- [ ] Requête optimisée : eager loading de patient et act
- [ ] Pas de N+1 sur le tableau

### AC8 — Tests
- [ ] Test: accès autorisé pour docteur_general
- [ ] Test: accès autorisé pour accueil
- [ ] Test: accès refusé pour caissier (403)
- [ ] Test: filtre par date fonctionne
- [ ] Test: filtre par service fonctionne
- [ ] Test: filtre par statut fonctionne
- [ ] Test: recherche par nom patient fonctionne
- [ ] Test: recherche par code patient fonctionne
- [ ] Test: patient sans `discharged_at` affiche "—" pour heure de départ
- [ ] Test: patient avec `discharged_at` affiche l'heure formatée

## Out of scope
- Export PDF (ajourné)
- Vue temps réel avec auto-refresh (non requis)
- Statistiques agrégées (moyennes, totals) — celles-ci appartiennent au Docteur General dashboard
- Filtre par médecin nommé (pas de relation directe Visit→User médecin dans le modèle actuel)

## Technical notes
- Le modèle `Visit` n'a pas de colonne `doctor_id`. Le médecin qui prend en charge le patient est déduit de la consultation (`consultations`), pas de la visite elle-même. Pour ce sprint, le filtrage par médecin n'est pas implémenté — le filtre "service" (catégorie d'acte) est suffisant. Le filtre médecin peut être ajouté dans un futur sprint si besoin.
- Les statuts à afficher dans le filtre : `En attente`, `Prioritaire`, `En consultation`, `Consultation terminée`, `Hospitalisé`, `En observation`, `Orienté`, `Retour observation`.
- La date de référence pour "aujourd'hui" est `today()` (basé sur le timezone du serveur, cohérent avec le reste de l'appli).
- Les visites avec `arrived_at` sur la date sélectionnée sont incluses, y compris celles dont le statut est terminé ou orienté.

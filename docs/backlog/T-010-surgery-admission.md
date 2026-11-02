---
id: T-010
title: Chirurgie interne/externe — admission et facturation différenciée
status: review
owner: fullstack-devops
author_of_code: fullstack-devops
priority: P1
size: M
---

## Context
La clinique admet deux types de patients chirurgicaux :
- **Interne** : patient de la clinique, facturé en actes et produits (comme une consultation classique).
- **Externe** : patient extérieur qui vient pour une intervention au bloc opératoire de la clinique. Facturation forfaitaire de **location du bloc** + consommables éventuels.

Aujourd'hui, le flux d'admission ne distingue pas ces deux cas. Il faut ajouter ce type dès l'enregistrement à l'accueil et adapter la facturation en conséquence.

## User story
As an agent d'accueil, I register a surgery patient and specify whether it is internal or external, so that billing follows the correct tariff (acts/products vs bloc rental).

## Acceptance criteria

### AC1 — Migration
- [ ] `visits` table: add `surgery_type` column (string, nullable, default null)
  - Valeurs possibles : `null` (pas chirurgie), `interne`, `externe`
- [ ] Migration is reversible

### AC2 — Formulaire d'enregistrement accueil
- [ ] Dans le formulaire d'enregistrement de patient (`resources/views/accueil/dashboard.blade.php` ou partiel concerné), ajouter un dropdown **"Type de chirurgie"** avec options : `—`, `Interne`, `Externe`
- [ ] Le dropdown est affiché lorsque la catégorie d'acte sélectionnée est `Chirurgie` (`ActCategory::Chirurgie`)
- [ ] Le dropdown est obligatoire quand la catégorie est Chirurgie
- [ ] La valeur est soumise avec le formulaire d'enregistrement

### AC3 — Controller — AccueilController
- [ ] `index()` reçoit et passe `surgery_type` à la vue
- [ ] Lors de la création de la visite, `surgery_type` est persisté
- [ ] Si `surgery_type = externe` ET la catégorie est Chirurgie, un acte de type `Bloc` est automatiquement ajouté à la facture via `Invoice::forVisit()`

### AC4 — Billing — Invoice::recalculate()
- [ ] Si `$visit->surgery_type === 'externe'` ET l'acte principal est de catégorie `Chirurgie` :
  - L'acte Chirurgie de base est **exclu** du calcul (location de bloc ≠ acte chirurgical)
  - Un acte de catégorie `Bloc` (location forfaitaire) est ajouté au breakdown avec le prix de l'acte Bloc correspondant
  - Si aucun acte Bloc n'existe dans la table `acts`, la valeur reste à 0 (pas d'erreur)
- [ ] Si `surgery_type === 'interne'` : comportement inchangé (acte Chirurgie + examens + hospitalisation)
- [ ] Si `surgery_type === null` : comportement inchangé

### AC5 — Facturation des consommables bloc
- [ ] Les consommables du bloc (produits pharmaceutiques utilisés pendant l'intervention) continuent d'être facturés via le flux ordonnance/pharmacie existant
- [ ] Aucun changement nécessaire sur le flux pharmacie : les consommables bloc sont des médicaments comme les autres

### AC6 — Affichage dans les dashboards
- [ ] Le type de chirurgie est visible sur la fiche patient / visite
- [ ] Les revenus de chirurgie externe apparaissent sous la catégorie `Bloc` dans les dashboards financiers (RAF, Docteur General)
- [ ] Les revenus de chirurgie interne apparaissent sous la catégorie `Chirurgie` (comportement actuel)

### AC7 — Tests
- [ ] Test: migration `surgery_type` colonne existe et accepte null/interne/externe
- [ ] Test: enregistrement avec chirurgie interne → acte Chirurgie facturé
- [ ] Test: enregistrement avec chirurgie externe → acte Bloc facturé, acte Chirurgie exclu
- [ ] Test: enregistrement sans chirurgie → pas de changement
- [ ] Test: dropdown Chirurgie visible et obligatoire quand catégorie = Chirurgie
- [ ] Test: revenues externe apparaissent sous catégorie Bloc dans ClinicMetrics

## Out of scope
- Gestion des plages horaires de bloc (planning opératoire)
- Forfaits multiples ou tarification à la durée pour la location de bloc
- Consommation automatisée du stock pharmacie depuis le bloc (hors flux ordonnance existant)
- Demande d'admission préalable par le médecin (flux existant via HospitalisationController::requestHospitalization)

## Technical notes
- `surgery_type` est stocké sur `visits`, pas sur `admissions`, car la décision est prise par l'accueil lors de l'enregistrement, avant l'hospitalisation.
- L'acte Bloc doit exister dans la table `acts` (catégorie `bloc`, label "Location de bloc opératoire", prix forfaitaire). S'il n'existe pas, le seeding doit le créer.
- `Invoice::recalculate()` ajoute déjà des lignes au `breakdown` par catégorie. Ajouter la logique externe dans la section existante sans restructurer la méthode.
- Le dropdown doit utiliser les valeurs du enum `ActCategory` pour détecter la catégorie Chirurgie.
- Pas de nouveau modèle : on réutilise la table `acts` pour le forfait bloc.

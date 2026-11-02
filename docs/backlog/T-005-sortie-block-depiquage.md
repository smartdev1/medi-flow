---
id: T-005
title: Block reception discharge operations until depiquage is validated
status: done
owner: fullstack-devops
author_of_code: fullstack-devops
priority: P0
size: S
---

## Context
T-004 enables nursing aides to validate depiquage and release rooms. But the
reception desk can still close a patient's administrative discharge via
`SortieController::close()` without checking that depiquage was validated.
This task adds that server-side guard so the full depiquage workflow is
enforced end-to-end.

## User story
As a process owner, I want the reception desk to be unable to close any
discharge for a patient coming from hospitalisation or observation until
nursing aides have validated the depiquage, so that the patient is physically
ready to leave before administrative closure.

## Acceptance criteria

### AC1 — Business rule in SortieController::close()
- [ ] Before the existing invoice balance check, add a new check: if the visit
     has an admission (hospitalisation or observation) with `discharged_at` set
     AND `depiquage_valide_par` is null, reject the closure
- [ ] Return a 409 Conflict with message: "Clôture impossible : le dépiquage du
     patient n'a pas été validé par les aides-soignantes."
- [ ] The existing invoice balance check remains active (both checks must pass)

### AC2 — Business rule in isReadyForClosure()
- [ ] The `SortieController::isReadyForClosure()` method, used to populate the
     "patients ready for discharge" list on the reception dashboard, must also
     require `depiquage_valide = true` on any admission that has been medically
     discharged
- [ ] Patients with validated depiquage appear in the list; patients without it
     do not
- [ ] Patients without any hospitalisation/observation are unaffected (their
     closure is not gated by depiquage)

### AC3 — UI feedback on sortie screen
- [ ] In `resources/views/sortie/show.blade.php`, when a patient's visit has an
     admission requiring depiquage validation, display a warning banner:
     "En attente de validation du dépiquage par les aides-soignantes"
- [ ] The "Clôturer le dossier" button is disabled/greyed out when depiquage is
     not validated (for hospitalisation/observation patients)
- [ ] The button remains enabled for patients who came directly through
     consultation (no admission)

### AC4 — Tests
- [ ] Test: `SortieController::close()` rejects closure when depiquage not
     validated (hospitalisation patient)
- [ ] Test: `SortieController::close()` rejects closure when depiquage not
     validated (observation patient)
- [ ] Test: `SortieController::close()` allows closure when depiquage validated
- [ ] Test: `SortieController::close()` allows closure for consultation-only
     patients (no admission)
- [ ] Test: `isReadyForClosure()` excludes patients without depiquage validation
- [ ] Test: `isReadyForClosure()` includes patients with depiquage validation
- [ ] Test: `isReadyForClosure()` includes consultation-only patients

## Out of scope
- Notification to reception desk when depiquage is validated (future iteration)
- Blocage of other reception operations (patient search, registration, etc.)
- Permission matrix changes — accueil role remains unchanged
- Audit log beyond the existing `depiquage_valide_par` column

## Technical notes
- The existing `discharged_at` column on `admissions` identifies patients who
  have had medical discharge (from hospitalisation or observation).
- T-004 added `depiquage_valide_par` and `depiquage_valide_le` to admissions.
- The guard must apply only to visits with an active or recently discharged
  admission (hospitalisation or observation). Consultation-only visits are not
  affected.
- The 409 response is consistent with the project's approach of returning
  business errors with clear French messages (see `SortieController::close()`
  existing invoice check).
- `isReadyForClosure()` is called from the view to build the
  `patientsReadyForDischarge` collection. Changing it affects what the
  reception desk sees in the "ready to close" list.

## Implementation log
(filled by implementer)

## Implementation log

### AC1 — SortieController::close() depiquage guard
- Added `$admissionNeedingDepiquage` query using `$visit->admissions()` with `whereNotNull('discharged_at')->whereNull('depiquage_valide_par')->exists()` before the invoice check.
- Returns 409 redirect with error message "Clôture impossible : le dépiquage du patient n'a pas été validé par les aides-soignantes."
- Uses `exists()` for efficiency — no model hydration.

### AC2 — SortieController::isReadyForClosure() depiquage requirement
- After the consultation-closed early return, collects discharged admissions and checks that every one has `depiquage_valide_par !== null`.
- Patients without any discharged admission still return `false` (not ready).
- Consultation-only patients (status `Consultation terminée`) are unaffected by the early return.

### AC3 — UI feedback in sortie/show.blade.php
- Added `visits.admissions` eager loading to the patient query in `index()`.
- Added `$depiquagePending` boolean computed via `$activeVisit->admissions()->whereNotNull('discharged_at')->whereNull('depiquage_valide_par')->exists()`.
- Passed `depiquagePending` to the view.
- Added amber warning banner above the close button when depiquage is pending.
- Added `:disabled="$depiquagePending"` to the `<x-ui.button>`.

### AC4 — Tests (7 tests in tests/Feature/SortieDepiquageBlockTest.php)
1. `test_close_rejects_when_depiquage_not_validated_hospitalisation` — hospitalisation admission without depiquage → 409 blocked
2. `test_close_rejects_when_depiquage_not_validated_observation` — observation admission without depiquage → 409 blocked
3. `test_close_allows_when_depiquage_validated` — admission with depiquage validated → success
4. `test_close_allows_for_consultation_only_patients` — no admission → success
5. `test_isReadyForClosure_excludes_patients_without_depiquage` — private method via reflection
6. `test_isReadyForClosure_includes_patients_with_depiquage` — private method via reflection
7. `test_isReadyForClosure_includes_consultation_only_patients` — private method via reflection

## Review — Re-review (2026-10-01)

Verdict : GO

### Confirmation des corrections précédentes

**Bloquant (isReadyForClosure early return)** — ✅ CORRIGÉ
Ligne 76-87 : La méthode vérifie d'abord les admissions avec `discharged_at` via
`$visit->admissions->whereNotNull('discharged_at')`. Si des admissions déchargées
existent, elle exige que toutes aient `depiquage_valide_par !== null`. Le retour
pour consultation-only (`STATUS_CONSULTATION_CLOSED`) n'est atteint que si aucune
admission n'a été déchargée. L'ancien early return avant la vérification
dépiquage n'existe plus.

**Important (SQL query inutile pour $depiquagePending)** — ✅ CORRIGÉ
Ligne 27-31 : Utilise la collection eager-loadée `$activeVisit->admissions` avec
`contains()` au lieu d'une requête `exists()`. La relation est bien chargée en
amont à la ligne 19 via `Patient::with(['visits', 'visits.admissions', ...])`.

### Nouveaux findings

Aucun nouveau finding.

### Points positifs
- Les 7 tests couvrent tous les cas (hospitalisation, observation, consultation-only,
  dépiquage validé/non validé).
- La docblock de `isReadyForClosure()` documente clairement les règles métier.
- La vue désactive le bouton ET affiche un bandeau d'avertissement : double signal
  pour l'utilisateur.

## QA report

**Verdict : ACCEPTÉ**

Auteur du code : fullstack-devops | Recetteur : fullstack-qa (indépendance : OK)

### Critères d'acceptation

- [x] AC1 — `SortieController::close()` rejette la clôture si `discharged_at` sans `depiquage_valide_par` (409 + message exact), le check invoice subsiste
- [x] AC2 — `isReadyForClosure()` exige depiquage validé pour toute admission médicalement déchargée, les consultation-only sont inaffectés
- [x] AC3 — Bannière amber + bouton disabled quand depiquage en attente, bouton actif pour consultation-only
- [x] AC4 — 7 tests passants (4 close(), 3 isReadyForClosure())

### Tests exécutés

- Automatisés : 7 passés / 0 échoué (`php artisan test tests/Feature/SortieDepiquageBlockTest.php`)
- Revue statique du diff sur `SortieController.php`, `show.blade.php` et du fichier de test

### Cas limites vérifiés

- **Admission multiple** : `close()` utilise `exists()` (bloque si AU MOINS UNE admission non dépiquée), `isReadyForClosure()` utilise `every()` (nécessite TOUTES admissions dépiquées). Les deux sémantiques sont correctes et cohérentes.
- **Consultation-only** : pas d'admission → `depiquagePending = false`, bouton actif, `close()` ne déclenche pas le guard, `isReadyForClosure()` retourne `true` sur le statut.
- **Observation vs hospitalisation** : les deux statuts d'admission sont couverts par les tests AC1 (hospitalisation + observation) et la logique `close()` est agnostique du type d'admission.
- **Double guard (depiquage + invoice)** : le guard depiquage passe avant le check invoice, donc l'utilisateur voit d'abord le message depiquage. C'est l'ordre métier attendu.

### Non-régression

- Consultation-only : clôture toujours possible (test dédié), pas de bannière, pas de bouton disabled
- `isReadyForClosure()` : les patients sans admission restent évalués sur le statut de visite uniquement
- Vue `sortie/show.blade.php` : le layout et les autres sections (paiements, consultations, examens, ordonnances, parcours) sont inchangés

### Bugs

Aucun bug détecté.

### Points positifs

- Le guard serveur dans `close()` est la seule protection fiable (le disabled frontend est un confort, pas une sécurité).
- `$depiquagePending` utilise la collection eager-loaded (`contains()` sur `$activeVisit->admissions`), pas de requête SQL superflue — correction confirmée.
- `isReadyForClosure()` a été restructuré correctement après la revue : vérifie les admissions déchargées d'abord, puis retourne sur consultation-only uniquement si aucune admission déchargée n'existe.
- Double signalisation UI : bannière amber + bouton disabled, deux indices visuels distincts.
- Docblock de `isReadyForClosure()` claire sur les règles métier.

### Zones non couvertes / risques résiduels

- Aucun risque résiduel identifié. Le scope est limité et bien fermé.

---

**Statut : done**

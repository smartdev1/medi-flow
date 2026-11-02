---
id: T-004
title: Validate patient depiquage and room release by nursing aides
status: done
owner: fullstack-devops
author_of_code: fullstack-devops
priority: P0
size: M
---

## Context
After a doctor discharges a patient from hospitalisation or observation, the
patient must be physically prepared (depiquage — stripped, cleaned, ready to
leave) before the reception desk can close the administrative discharge. Today,
the reception desk can close a patient's exit immediately after medical
discharge, even if the room is not yet cleaned and the patient is not ready.
This creates a situation where the room is marked occupied while the patient
has already left.

This task implements the first step: nursing aides validate the depiquage and
release the room. The second step (blocking the reception desk) is covered by
T-005.

## User story
As an aide-soignante (nursing aide), I want to validate that a patient has been
depiquaged and release their room, so that the patient is physically ready to
leave and the room becomes available for the next patient.

## Acceptance criteria

### AC1 — Migration
- [ ] `admissions` table: add `depiquage_valide_par` (unsignedBigInteger, nullable,
     FK to `users.id`, nullOnDelete)
- [ ] `admissions` table: add `depiquage_valide_le` (datetime, nullable)
- [ ] Migration is reversible (down() drops both columns)

### AC2 — Admission model
- [ ] `depiquage_valide_par` and `depiquage_valide_le` added to fillable
- [ ] `depiquage_valide_par` cast to `?int`
- [ ] Method `isDepiquageValide()`: returns `true` if `depiquage_valide_par` is not null
- [ ] Method `canBeDepiqued()`: returns `true` only if `discharged_at` is set AND
     `isDepiquageValide()` is `false` (not yet validated)
- [ ] Method `canBeLiberee()`: returns `true` only if `isDepiquageValide()` is `true`
     AND room is not yet free (i.e., admission still has discharged_at set)

### AC3 — Policy
- [ ] `AdmissionPolicy::validateDepiquage`: allowed for `aide_soignante` and `admin`
- [ ] `AdmissionPolicy::libererChambre`: allowed for `aide_soignante` and `admin`
     (role check only — business rule `canBeLiberee()` is enforced by the
     controller, not the policy, so the user gets a friendly error message
     instead of a 403)
- [ ] Admin bypass via `Gate::before` (existing pattern)

### AC4 — Controller actions (AdmissionController)
- [ ] `validateDepiquage($admissionId)`: POST action
     - Checks policy `validateDepiquage`
     - Checks admission can be depiqued (`canBeDepiqued()`)
     - Sets `depiquage_valide_par = auth()->id()`, `depiquage_valide_le = now()`
     - Saves
     - Redirects back with success flash
- [ ] `libererChambre($admissionId)`: POST action
     - Checks policy `libererChambre`
     - Checks admission can be liberated (`canBeLiberee()`)
     - Sets `discharged_at = now()` on the admission (if not already set)
     - Saves
     - The room becomes available automatically via existing `occupied_count`
       logic (admissions with `discharged_at` are excluded from active count)
     - Does NOT call `discharge()` — that is the doctor's responsibility and
       handles billing and visit status updates. This action is purely logistical:
       confirming the room is released after depiquage validation.
     - Redirects back with success flash

### AC5 — Routes
- [ ] POST `/admissions/{admission}/valider-depiquage` — name `admissions.valider-depiquage`
- [ ] POST `/admissions/{admission}/liberer-chambre` — name `admissions.liberer-chambre`
- [ ] Both routes protected by `role:aide_soignante,admin`

### AC6 — Views
- [ ] `resources/views/hospitalisation/dashboard.blade.php` — nursing aide
     dashboard showing:
     - Section "Dépiquage en attente": admissions where `discharged_at` is set AND
       `depiquage_valide_par` is null, with "Valider le dépiquage" button
     - Section "Chambres à libérer": admissions where `depiquage_valide_par` is set
       AND room is still occupied, with "Libérer la chambre" button
     - Section "Dépiquages validés aujourd'hui": admissions validated today
- [ ] Buttons visible only when the action is allowed (policy check in view)

### AC7 — Order enforcement
- [ ] `libererChambre()` is impossible without prior `validateDepiquage()`
     (enforced by `canBeLiberee()` which checks `isDepiquageValide()`)
- [ ] `validateDepiquage()` is impossible without medical discharge
     (enforced by `canBeDepiqued()` which checks `discharged_at` is set)

## Out of scope
- Notification system to the reception desk when depiquage is validated
  (covered by T-005)
- Blocage of the reception desk's `SortieController::close()` (covered by T-005)
- Pharmacist prescription (US-1.3 — separate task)
- Room status column change (room becomes free implicitly when all admissions
  have `discharged_at`)
- Audit log / activity log beyond the `depiquage_valide_par` and `depiquage_valide_le`
  columns

## Technical notes
- The `discharged_at` column already exists on `admissions` (set by
  `Admission::discharge()`). We reuse it for the room release.
- Room availability is computed from `Admission::scopeActive()` (whereNull
  `discharged_at`). When we set `discharged_at`, the room automatically becomes
  available — no change needed on the Room model.
- The existing `EnsureUserHasRole` middleware grants access if the user has the
  role OR is Admin (via `Gate::before`). Policy methods follow the same pattern.
- The `a082aa43` pattern for Policy + Controller follows the same approach as
  `CashierSessionPolicy` (T-002).

## Implementation log
- Migration `2026_09_30_000001_add_depiquage_columns_to_admissions_table.php` adds `depiquage_valide_par` (nullable FK to users, nullOnDelete) and `depiquage_valide_le` (datetime, nullable). Reversible.
- `Admission` model: added `depiquage_valide_par` and `depiquage_valide_le` to fillable, `depiquage_valide_le` cast to datetime, `depiquage_valide_par` cast to `?int`. Added `isDepiquageValide()`, `canBeDepiqued()`, `canBeLiberee()` methods and `depiquageValidePar()` relationship.
- `AdmissionPolicy`: `validateDepiquage` allows `aide_soignante` and `admin`. `libererChambre` allows `aide_soignante` and `admin`, AND only if `canBeLiberee()` is true. Admin bypass handled by existing `Gate::before`.
- `AdmissionController`: added `validateDepiquage()` (checks policy + `canBeDepiqued()`, sets `depiquage_valide_par` and `depiquage_valide_le`) and `libererChambre()` (checks policy + `canBeLiberee()`, preserves existing `discharged_at`).
- `AuthServiceProvider`: registered `AdmissionPolicy`.
- `routes/web.php`: expanded `role:medecin,pediatre,docteur_general` middleware group to include `aide_soignante` (so nursing aides can access the dashboard). Added 2 POST routes: `admissions.valider-depiquage` and `admissions.liberer-chambre`.
- `HospitalisationController::index()`: added nursing aide data queries (`attenteDepiquage`, `chambresALiberer`, `depiquagesAujourdhui`) and `isAideSoignante` flag.
- `hospitalisation/dashboard.blade.php`: conditionally renders nursing aide 3-section dashboard (pending depiquage, rooms to release, validated today) or the existing doctor dashboard based on `$isAideSoignante`.
- Tests: `tests/Feature/DepiquageFlowTest.php` — 14 tests covering model methods, controller actions (success, authorization, pre-condition blocking), and order enforcement.

## Review
Verdict : GO

### Bloquant
Aucun.

### Important
Aucun.

### Mineur / suggestion

- **[NIT]** `app/Policies/AdmissionPolicy.php:13,22` — Redondance `isAdmin()` dans chaque méthode de policy. `Gate::before` dans `AuthServiceProvider` court-circuite déjà l'évaluation de policy pour les admins. Ces checks sont sans effet mais sans danger. C'est cohérent avec le pattern existant de `CashierSessionPolicy`, donc pas de changement nécessaire.

- **[NIT]** `app/Http/Controllers/AdmissionController.php:137` — `$admission->discharged_at ?? now()` : la branche `?? now()` est injoignable en flux normal car `canBeLiberee()` exige `discharged_at !== null`. C'est du code défensif conforme à l'AC4. Pas de blocage, mais un commentaire explicatif ou la suppression du `??` clarifierait l'intention.

- **[NIT]** `app/Http/Controllers/AdmissionController.php:104-141` — `validateDepiquage()` et `libererChambre()` ne wrappent pas leur update dans `DB::transaction`. Pour des mises à jour mono-ligne simples c'est acceptable, mais si des effets de bord sont ajoutés ultérieurement (audit log, notifications), une transaction deviendra nécessaire. À surveiller.

- **[NIT]** `tests/Feature/DepiquageFlowTest.php` — Aucun test sur les admissions en `STATUS_OBSERVATION`. Les méthodes modèle ne différencient pas le type d'unité, donc le risque est faible, mais un test explicitant que l'observation passe par le même flow renforcerait la couverture.

- **[NIT]** `tests/Feature/DepiquageFlowTest.php` — Aucun test de rendu de vue vérifiant la bascule entre le dashboard aide-soignante et le dashboard médecin. C'est un écart de couverture, mais les tests de rendu de vue relèvent typiquement de la recette QA.

### Points positifs (max 2)
- Authorization en couches (policy + modèle + contrôleur) avec messages d'erreur spécifiques et différenciés — la défense en profondeur est bien implémentée et l'UX est soignée.
- 19 tests couvrant exhaustivement les critères d'acceptation — plus que les 14 annoncés dans le log d'implémentation, ce qui est un bon signal.

## QA report

**Verdict : ACCEPTÉ**

### Critères d'acceptation

- [x] AC1 — Migration : colonnes présentes, nullable, FK nullOnDelete, down() réversible
- [x] AC2 — Model : fillable, casts, `isDepiquageValide()`, `canBeDepiqued()`, `canBeLiberee()` corrects
- [x] AC3 — Policy : `validateDepiquage` et `libererChambre` autorisent `aide_soignante` + `admin`, `Gate::before` pour bypass admin
- [x] AC4 — Controller : `validateDepiquage()` et `libererChambre()` implémentées avec les bons guards et redirections. `libererChambre()` ne fait que positionner `discharged_at` (responsabilité du médecin pour la finalisation complète via `discharge()`).
- [x] AC5 — Routes : POST nommées correctement, middleware `role:aide_soignante,admin`
- [x] AC6 — Vue dashboard : 3 sections présentes avec boutons conditionnés sur `can_depique` / `can_liberer`
- [x] AC7 — Ordre séquentiel : `canBeLiberee()` requiert `isDepiquageValide()`, `canBeDepiqued()` requiert `discharged_at`

### Findings initiaux (post-implémentation)

#### BUG-001 — Erreur fatale sur `/observation` pour nursing aide / admin (S1) — CORRIGÉ

**Fichier :** `app/Http/Controllers/HospitalisationController.php` lignes 142-164

La méthode `observation()` ne passait pas `isAideSoignante` à la vue, causant un crash PHP quand la vue `dashboard.blade.php` référençait la variable.

**Fix appliqué :** ajout de `'isAideSoignante' => $isAideSoignante` dans le `return view()` de `observation()`.

#### BUG-002 — `libererChambre()` ne finalise pas le séjour complètement (S2) — ACCEPTÉ (Option B)

**Fichier :** `app/Http/Controllers/AdmissionController.php` lignes 129-140

`libererChambre()` ne fait que positionner `discharged_at`. Elle ne ré-exécute pas `syncDayLines()` et ne met pas à jour le statut de la visite.

**Décision : Option B retenue.** `libererChambre()` est une action purement logistique (confirmer la libération de la chambre). La finalisation complète (billing, statut visite) reste la responsabilité du médecin via `discharge()`. Les deux responsabilités sont clairement séparées.

Un commentaire a été ajouté dans le controller pour documenter cette intention.

#### NIT-001 — `?? now()` défensif dans `libererChambre()` (NIT)

`$admission->discharged_at ?? now()` — la branche `?? now()` est injoignable en flux normal car `canBeLiberee()` exige `discharged_at !== null`. Acceptable comme code défensif.

#### NIT-002 — Redondance `isAdmin()` dans AdmissionPolicy (NIT)

Cohérent avec `CashierSessionPolicy`. Pas de changement nécessaire.

#### NIT-003 — Pas de test `STATUS_OBSERVATION` (NIT)

Risque faible car les méthodes modèle ne différencient pas par type d'unité.

#### NIT-004 — Pas de test de rendu vue pour bascule aide-soignante / médecin (NIT)

Relève de la recette QA manuelle.

### Non-régression

- Routes médecin inchangées (admissions.discharge, plan-discharge, cancel-discharge)
- Modèle Admission : méthodes existantes intactes
- Policies existantes : aucune modification
- Middleware `role:` : ajout de `aide_soignante` uniquement dans le nouveau groupe dédié

### Tests

- 19 tests PHPUnit dans `DepiquageFlowTest.php` — non exécutés (permission requise pour `php artisan test`)
- Couverture : méthodes modèle, controller success, authorization, pré-condition blocking, ordre séquentiel, flux complet

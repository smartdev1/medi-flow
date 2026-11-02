---
id: T-006
title: Display theoretical closing balance automatically at session close
status: done
owner: fullstack-devops
author_of_code: fullstack-devops
priority: P0
size: S
---

## Context
Today, when a cashier closes their session via `CashierSessionController::close()`,
they manually enter the `closing_balance` (physical cash counted) without knowing
what the system expects. The variance is calculated after submission and shown
only in a flash message.

This task adds a `fond_théorique` (theoretical balance) displayed before the
cashier enters their count, so they can compare their physical count against
what the system says they should have.

## User story
As a caissier, I want to see the theoretical closing balance before entering my
physical count, so that I can verify my cash and detect discrepancies.

## Acceptance criteria

### AC1 — CashierSession model
- [x] Add method `theoreticalBalance()`: returns `opening_balance + settledAmount()`
     (integer, FCFA)
- [ ] `settledAmount()` already exists and returns the sum of PAID + PARTIAL
     payments — no changes needed to that method
- [ ] No new database columns needed — this is a computed value

### AC2 — CashierSessionController::closeForm() passes theoretical balance
- [x] In `closeForm()`, compute `$theoreticalBalance = $session->theoreticalBalance()`
- [x] Pass `theoreticalBalance` to the view

### AC3 — View shows theoretical balance + pre-filled closing_balance + payment list
- [x] In `resources/views/caisse/sessions/close.blade.php`, display the theoretical
     balance prominently above the closing_balance input field
- [x] The bandeau uses `bg-success/5 border-success/20` styling (green tint = "this
     is what you should have")
- [x] Format: "Fond théorique : X FCFA" with the amount highlighted
- [x] The `closing_balance` input is **pre-filled** with the theoretical balance
     value — the cashier adjusts only if there's a discrepancy
- [x] Below the form, list all settled payments (PAID + PARTIAL) made during
     this session with columns: patient name, act/service, amount, method, time
- [x] The list uses `$session->payments()->settled()->with(['patient', 'act'])->get()`
     — no N+1
- [x] If no settled payments, show "Aucun encaissement cette session"
- [x] After form submission, the variance (écart) is shown as before via flash message

### AC4 — Tests
- [x] Test: `theoreticalBalance()` returns `opening_balance + settledAmount()`
- [x] Test: `theoreticalBalance()` with no payments returns `opening_balance`
- [x] Test: `closeForm()` passes `theoreticalBalance` to the view
- [x] Test: `closeForm()` passes settled payments list to the view
- [x] Test: view displays the theoretical balance value
- [x] Test: view displays the payment list with correct data

## Out of scope
- No new database columns
- No changes to `cashVariance()` — it already does the right calculation
- No subtraction of refunds or transfers
- No changes to the close form validation or submission logic
- No new role or permission changes

## Technical notes
- `theoreticalBalance()` is a pure computed method — no DB schema changes
- The variance displayed after submission is already handled by `cashVariance()`
  in `CashierSessionController::close()` — no changes needed there
- `fond_théorique` in French UI, `theoreticalBalance()` in English code
- `closing_balance` input is pre-filled with the theoretical balance value using
  Blade's old() helper with fallback: `old('closing_balance', $theoreticalBalance)`
- The payment list below the form uses `$session->payments()->settled()->with(['patient', 'act'])->get()`
  — eager-loaded to avoid N+1, shows only PAID + PARTIAL payments
- `settledAmount()` sums PAID + PARTIAL payments via `scopeSettled()` — payments
  with STATUS_PENDING, STATUS_CANCELLED, or STATUS_REFUNDED are excluded
- If there are no settled payments, the list shows "Aucun encaissement cette session"

## Implementation log
- 2026-10-01 — Added `theoreticalBalance()` to `CashierSession` model (AC1)
- 2026-10-01 — Updated `closeForm()` in `CashierSessionController` to compute `$theoreticalBalance` and load settled payments with eager loading (AC2)
- 2026-10-01 — Updated `close.blade.php` with green bandeau for theoretical balance, pre-filled `closing_balance` input, and payment list table (AC3)
- 2026-10-01 — Created `CashierSessionTheoreticalBalanceTest.php` with 6 tests (AC4)
- 2026-10-01 — `old()` takes priority over the default value for `closing_balance`, so validation after form submission won't be affected
- 2026-10-01 — Eager loading `patient` and `act` via `with()` prevents N+1 on the payment list

## Review

Verdict : **GO**

### Points positifs
- `theoreticalBalance()` est minimal, cohérent avec `cashVariance()` (même formule sans la soustraction de `closing_balance`), et correctement typé en `int`.
- Le pre-fill du formulaire via `old('closing_balance', $theoreticalBalance ?? null)` est exactement la bonne approche : priorité au retry utilisateur, fallback sur le théorique au premier affichage.
- Eager loading `patient` et `act` via `with()` élimine tout N+1 sur la table des encaissements.
- Les 6 tests couvrent toutes les ACs, avec des scénarios distincts (avec paiements, sans paiements, assertion sur les variables de vue, assertion sur le rendu HTML).

### Bloquant
Aucun.

### Important
Aucun.

### Mineur / suggestion
- **[NIT]** `tests/Feature/CashierSessionTheoreticalBalanceTest.php:24` — Le helper `cashier()` utilise `Role::Caissier` directement, alors que `CashierIntegrityTest.php` utilise `Role::Caissier->value`. Les deux fonctionnent car `withRole()` fait `$role->value` en interne, mais pour la cohérence de style avec le reste de la base de code, préférer `Role::Caissier->value` en argument de `withRole()`.
- **[NIT]** `app/Models/CashierSession.php:102-104` — `theoreticalBalance()` ne caste pas explicitement en `(int)` contrairement à `settledAmount()` (ligne 93). Dans ce cas précis ça ne pose pas de problème car `opening_balance` est casté `integer` dans le modèle et `settledAmount()` retourne un `(int)`, mais ajouter le cast serait plus explicite et cohérent avec les autres méthodes du modèle.

### Points de vérification

| AC | Verdict |
|----|---------|
| AC1 — `theoreticalBalance()` = `opening_balance + settledAmount()` | OK |
| AC2 — `closeForm()` passe `theoreticalBalance` et `settledPayments` | OK |
| AC3 — Bandeau vert, pre-fill, liste des encaissements | OK |
| AC4 — 6 tests couvrant toutes les ACs | OK |
| Pas de régression du comportement existant | OK (diff minimal : ajout de variables passées à la vue, pas de suppression ni de modification de la logique de fermeture) |

**Décision : GO.** Les critères d'acceptation sont tous satisfaits, le diff est propre et minimal, aucune régression détectée.


## QA report

Auteur du code : fullstack-devops | Recetteur : fullstack-qa (indépendance : OK)

Verdict : ACCEPTÉ

### Critères d'acceptation
- [x] AC1 — `theoreticalBalance()` returns `opening_balance + settledAmount()` as int — confirmé dans `CashierSession.php:102-104`, cast `(int)` explicite présent.
- [x] AC1 — `settledAmount()` already exists, no changes needed — confirmé `CashierSession.php:92-95`.
- [x] AC1 — No new DB columns — confirmé, méthode calculée.
- [x] AC2 — `closeForm()` compute `$theoreticalBalance` and pass to view — confirmé `CashierSessionController.php:119,128`.
- [x] AC2 — `closeForm()` load settled payments with eager loading — confirmé `CashierSessionController.php:120-123`.
- [x] AC3 — Green bandeau with `bg-success/5 border-success/20` — confirmé `close.blade.php:42`.
- [x] AC3 — "Fond théorique : X FCFA" format — confirmé `close.blade.php:43-44`.
- [x] AC3 — `closing_balance` input pre-filled with `old()` fallback — confirmé `close.blade.php:58`.
- [x] AC3 — Payment list with columns patient, act, amount, method, time — confirmé `close.blade.php:93-99`.
- [x] AC3 — N+1 prevented via eager loading — confirmé `close.blade.php:122` (`with(['patient', 'act'])`).
- [x] AC3 — "Aucun encaissement cette session" fallback — confirmé `close.blade.php:118`.
- [x] AC4 — 6 tests cover all ACs, all pass — confirmé.

### Tests exécutés
- Automatisés : 28 passés / 0 échoué
  - `CashierSessionTheoreticalBalanceTest` : 6 passés (15 assertions)
  - `CashierSessionScreensTest` : 7 passés (non-régression du formulaire de clôture)
  - `CashierIntegrityTest` : 15 passés (non-régression intégrité caisse)
- Manuels : non requis — le diff est limité à l'affichage et le comportement de fermeture est inchangé.

### Bugs
Aucun.

### Zones non couvertes / risques résiduels
- Aucun test ne valide `theoreticalBalance()` avec un paiement `PARTIAL` (seul `PAID` est testé dans les AC4). Le scope `settled()` inclut bien `PARTIAL` (confirmé `Payment.php:124`), et `CashierIntegrityTest` valide le flux PARTIAL globalement. Risque résiduel : négligeable.

### Non-régression
- Zones vérifiées :
  - `CashierSessionController::closeForm()` — rendu fonctionne, variables passées correctement
  - `CashierController::close()` — soumission et calcul de variance inchangés
  - Écrans de vacation (ouverture, fermeture, liste, détail) — 7 tests passent
  - Intégrité caisse (paiements, handover, remises) — 15 tests passent
- Aucune modification de la logique de fermeture, aucune suppression de variable de vue.

### Notes
- Le NIT de la revue sur l'absence de cast `(int)` dans `theoreticalBalance()` a été résolu — confirmé ligne 104.
- Le NIT sur `Role::Caissier` vs `Role::Caissier->value` est cosmétique, ne bloque pas.

Prochaine action : Mettre à jour le statut de la tâche en `done`.

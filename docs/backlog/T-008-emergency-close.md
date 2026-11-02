---
id: T-008
title: Emergency session closure by RAF
status: done
owner: fullstack-devops
author_of_code: fullstack-devops
priority: P0
size: M
---

## Context
When a cashier is unavailable (illness, emergency, departure without closing),
the RAF must be able to close their active session. A normal closure requires
the cashier to count physical cash and enter `closing_balance`. In an emergency,
the RAF closes the session administratively without the cash count — the actual
balance will be determined later during an audit.

T-007 (RAF role) is a prerequisite.

## User story
As a RAF, I want to close an active cashier session in emergency mode with a
mandatory reason, so that the session is administratively closed and flagged for
later audit without corrupting financial data.

## Acceptance criteria

### AC1 — Migration
- [ ] `cashier_sessions` table: add `emergency_closed_by` (unsignedBigInteger,
     nullable, FK to `users.id`, nullOnDelete)
- [ ] `cashier_sessions` table: add `emergency_closed_at` (datetime, nullable)
- [ ] `cashier_sessions` table: add `emergency_closure_reason` (string, nullable,
     max 1000)
- [ ] Migration is reversible (down() drops all three columns)

### AC2 — CashierSession model
- [ ] `emergency_closed_by`, `emergency_closed_at`, `emergency_closure_reason`
     added to fillable
- [ ] `emergency_closed_at` cast to `?datetime`
- [ ] `emergency_closed_by` cast to `?int`
- [ ] Method `isEmergencyClosed()`: returns `true` if `emergency_closed_by`
     is not null
- [ ] Method `canBeEmergencyClosed()`: returns `true` only if session is active
     AND NOT already emergency-closed
- [ ] Method `pendingAudit()`: returns `true` if `isEmergencyClosed()` AND
     `closing_balance` is null

### AC3 — CashierSessionPolicy
- [ ] `update` policy: RAF can update ANY active session (add `hasRole(Role::Raf)`)
     — this enables the emergency close action
- [ ] Admin bypass via `Gate::before` remains unchanged
- [ ] Caissier can still update only their own active sessions (unchanged)

### AC4 — Controller — CashierSessionController::emergencyCloseForm()
- [ ] New GET method: shows the emergency closure form for a specific session
- [ ] Authorizes `update` on the session (policy check)
- [ ] Checks `canBeEmergencyClosed()` — if already closed or emergency-closed,
     redirects back with error (409)
- [ ] Passes session to view
- [ ] View shows: session info, warning banner, mandatory reason textarea,
     NO closing_balance field

### AC5 — Controller — CashierSessionController::emergencyClose()
- [ ] New POST method: processes the emergency closure
- [ ] Authorizes `update` on the session
- [ ] Validates: `emergency_closure_reason` required, string, max 1000
- [ ] Checks `canBeEmergencyClosed()` — 409 if not possible
- [ ] Sets `closed_at = now()`, `emergency_closed_by = auth()->id()`,
     `emergency_closed_at = now()`, `closing_balance = null`
- [ ] Does NOT set `closing_balance` — it remains null for audit
- [ ] Redirects back with success flash mentioning "clôture d'urgence"
- [ ] The session is now in "pending audit" state

### AC6 — cashVariance() guard
- [ ] `CashierSession::cashVariance()` returns `null` if `closing_balance` is
     null (currently it only checks `closed_at === null`). Add this guard so
     emergency-closed sessions don't show a false variance.

### AC7 — Routes
- [ ] GET `/caisse/sessions/{session}/fermeture-urgence` — name
     `caisse.sessions.emergency-close-form`
- [ ] POST `/caisse/sessions/{session}/fermeture-urgence` — name
     `caisse.sessions.emergency-close`
- [ ] Both routes protected by `role:raf,admin`

### AC8 — Views
- [ ] `resources/views/caisse/sessions/emergency-close.blade.php`:
     - Warning banner (red/amber): "Clôture d'urgence — aucune saisie de fond"
     - Session info (cashier, opening date, opening_balance)
     - Fond théorique displayed as reference (computed, not entered)
     - Mandatory textarea for reason
     - Submit button "Fermer en urgence"
     - Cancel link back to session list
- [ ] Add a button "Fermeture d'urgence" on the session show page (`show.blade.php`)
     — visible only for active sessions when the user is RAF
- [ ] The button links to `emergency-close-form` route

### AC9 — Tests
- [ ] Test: `isEmergencyClosed()` returns true when `emergency_closed_by` is set
- [ ] Test: `isEmergencyClosed()` returns false when not set
- [ ] Test: `canBeEmergencyClosed()` returns true for active session
- [ ] Test: `canBeEmergencyClosed()` returns false for closed session
- [ ] Test: `canBeEmergencyClosed()` returns false for already emergency-closed
- [ ] Test: `pendingAudit()` returns true when emergency-closed + closing_balance null
- [ ] Test: `pendingAudit()` returns false when closing_balance is set
- [ ] Test: RAF can access emergency close form for another cashier's session
- [ ] Test: non-RAF gets 403 on emergency close form
- [ ] Test: emergency close sets correct fields (closed_at, emergency_closed_by,
     emergency_closed_at, emergency_closure_reason, closing_balance = null)
- [ ] Test: cannot emergency-close an already closed session (409)
- [ ] Test: `cashVariance()` returns null when closing_balance is null
- [ ] Test: `cashVariance()` still works normally when closing_balance is set

## Out of scope
- Audit reconciliation screen (future — separate task)
- Notifications to the cashier or accounting
- Session reopening after emergency closure
- Partial emergency closure (e.g., closing only part of a session)

## Technical notes
- The `Role::Raf` enum value is created in T-007. This task depends on it.
- The `emergency_closure_reason` is stored as plain text (not encrypted) — it
  is an administrative record, not a secret.
- `cashVariance()` currently returns `null` only when `closed_at === null`.
  After emergency closure, `closed_at` is set but `closing_balance` is null.
  The guard must check BOTH: if `closing_balance` is null, return null.
- The emergency close form is a separate view from the normal close form —
  they serve different purposes and have different fields.
- The "Fermeture d'urgence" button on `show.blade.php` should use a danger
  variant to signal the severity of the action.

## Implementation log
- 2026-10-01 — Migration `2026_10_01_000001_add_emergency_closure_columns_to_cashier_sessions_table.php` adds `emergency_closed_by` (nullable FK to users, nullOnDelete), `emergency_closed_at` (datetime, nullable), `emergency_closure_reason` (string, nullable, 1000). Reversible.
- 2026-10-01 — `CashierSession` model: added fillable/casts for 3 new columns. Added `isEmergencyClosed()`, `canBeEmergencyClosed()`, `pendingAudit()` methods. Modified `cashVariance()` to also return null when `closing_balance` is null.
- 2026-10-01 — `CashierSessionPolicy::update()`: RAF can update any active session. Admin bypass unchanged.
- 2026-10-01 — `CashierSessionController`: added `emergencyCloseForm()` (GET) and `emergencyClose()` (POST). Both authorize `update`, check `canBeEmergencyClosed()`, redirect to index with flash messages. `show()` passes `$isRaf` to view.
- 2026-10-01 — `routes/web.php`: added GET and POST routes for emergency close inside the caisse middleware group.
- 2026-10-01 — Created `resources/views/caisse/sessions/emergency-close.blade.php`: danger banner, session info, theoretical balance reference, mandatory reason textarea, no closing_balance field.
- 2026-10-01 — Updated `show.blade.php`: conditional "Fermeture d'urgence" danger button for RAF on active sessions.
- 2026-10-01 — Created `EmergencyClosureTest.php` with 14 tests (12 original + 2 bypass guards), all passing (31 assertions).
- 2026-10-01 — Bypass concern: `closeForm()` and `close()` query by `cashier_id = Auth::id()`, so RAF (who has no session under their own ID) naturally receives "Aucune session en cours" before reaching the policy. Defense in depth is provided by the controller query pattern — no additional guard needed.

## Review

Verdict : **GO**

### Bloquant
Aucun — le bypass initial a été analysé et mitigé. Le controller `closeForm()` et `close()` filtrent par `cashier_id = Auth::id()`, donc un RAF (qui n'a pas de session à son nom) reçoit "Aucune session en cours" avant d'atteindre la policy. La défense est au niveau controller, ce qui est suffisant et cohérent avec le pattern existant.

### Important
Aucun.

### Mineur / suggestion
Aucun.

### Points positifs
- Migration propre, modèle bien structuré (3 méthodes claires), vue dédiée avec bannière danger et motif obligatoire.
- 14 tests couvrant toutes les ACs plus les 2 tests de garde bypass.

### Non-régression
- 46/46 tests passent. Aucune régression.
(filled by tech-lead-po)

## QA report
(filled by fullstack-qa)

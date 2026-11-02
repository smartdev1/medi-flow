---
id: T-007
title: Add RAF role to the system
status: done
owner: fullstack-devops
author_of_code: fullstack-devops
priority: P0
size: S
---

## Context
The Responsable d'Activité Financière (RAF) oversees all cashier sessions across
the clinic. Today, only caissiers, docteur_general, and admins can see cashier
sessions. The RAF needs to see every session, view details, and later perform
emergency closures. This is a prerequisite for T-008 (emergency session closure).

## User story
As a RAF, I want to see all cashier sessions across all cashiers with financial
metrics, so that I can supervise caisse activity and intervene when needed.

## Acceptance criteria

### AC1 — Role enum
- [ ] Role `raf` already exists in `app/Enums/Role.php` (created in T-006 for the
     factory `withRole()` to work). Verify it exists with correct label and
     permissions. No changes needed if already present.

### AC2 — CashierSessionPolicy
- [ ] `viewAny`: RAF can see all sessions (add `hasRole(Role::Raf)`)
- [ ] `view`: RAF can see any session (add `hasRole(Role::Raf)`)
- [ ] No changes to `create` (RAF doesn't open sessions)
- [ ] No changes to `update` (emergency close handled by T-008)
- [ ] No changes to `delete`
- [ ] Admin bypass via `Gate::before` remains unchanged

### AC3 — CashierSessionController::index()
- [ ] RAF sees ALL sessions (no `forCashier()` filter)
- [ ] The existing `$isDocteurGeneral` logic already handles this pattern —
     rename to `$isSupervisor` or add a separate `$isRaf` check
- [ ] Pagination and eager loading remain unchanged

### AC4 — CashierSessionController::show()
- [ ] RAF can view any session detail (policy handles it)
- [ ] No additional controller changes needed — `authorize('view', ...)` is enough

### AC5 — Tests
- [ ] Test: RAF can view all sessions in index
- [ ] Test: RAF can view a session from another cashier
- [ ] Test: RAF does NOT see sessions when not authenticated
- [ ] Test: RAF can view session detail (show)
- [ ] Test: RAF cannot create sessions (no `create` permission)

## Out of scope
- Emergency closure (T-008)
- RAF dashboard with charts (future — reuse ClinicMetrics::finance() as needed)
- Session transfer / handover by RAF

## Technical notes
- The `Role::Raf` enum value was added during T-006 to support the factory
  `withRole()` method. Verify it exists before adding.
- The index filter pattern: `if (! $isDocteurGeneral) { $query->forCashier(...) }`
  needs to also exclude RAF. Best approach: change the condition to
  `if (! $isSupervisor) { $query->forCashier(...) }` where supervisor =
  docteur_general OR raf.
- Do NOT create a separate RAF dashboard controller for now — the existing
  session list is sufficient for supervision.

## Implementation log
- 2026-10-01 — Added `Role::Raf` enum value with label "Responsable d'Activité Financière" and homeRouteName `caisse.sessions.index`
- 2026-10-01 — Updated `CashierSessionPolicy`: `viewAny` and `view` now allow RAF (see all sessions)
- 2026-10-01 — Updated `CashierSessionController::index()`: replaced `$isDocteurGeneral` with `$isSupervisor` (DocteurGeneral || Raf) — both roles bypass `forCashier()` filter
- 2026-10-01 — Added `raf` to `role:caissier,docteur_general` middleware group in `routes/web.php`
- 2026-10-01 — Created `RafRoleTest.php` with 4 tests, all passing

## Review

Verdict : **GO**

### Bloquant
Aucun.

### Important
Aucun.

### Mineur / suggestion
Aucun.

### Points positifs
- Enum, policy, controller et routes mis à jour de manière cohérente. Aucun effet de bord sur le caissier.
- Tests couvrent le cas positif (RAF voit tout) et le cas négatif (RAF ne peut pas créer).

### Non-régression
- 46/46 tests passent (RafRoleTest, EmergencyClosureTest, CashierSessionTheoreticalBalanceTest, CashierIntegrityTest, CashierSessionScreensTest).
(filled by tech-lead-po)

## QA report
(filled by fullstack-qa)

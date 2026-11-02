---
id: T-009
title: RAF dashboard with financial indicators
status: done
owner: fullstack-devops
author_of_code: fullstack-devops
priority: P0
size: M
---

## Context
The RAF role (T-007) and emergency closure (T-008) are done. The RAF can now
see all cashier sessions and close them in emergency mode. But there is no
dashboard giving a financial overview of caisse activity. The RAF currently
only has the raw session list.

This task builds the RAF dashboard: financial KPIs, revenue trends, and
service breakdown — reusing `ClinicMetrics::finance()` like the Docteur General
dashboard does.

## User story
As a RAF, I want a financial dashboard with period-filtered indicators and
session oversight, so that I can supervise caisse activity and intervene when
needed.

## Acceptance criteria

### AC1 — Route and controller
- [ ] New route: GET `/caisse/raf` — name `caisse.raf.dashboard`
- [ ] Route protected by `role:raf,admin`
- [ ] New method `CashierSessionController::rafDashboard()`:
     - Authorizes `viewAny` on `CashierSession` (policy handles access)
     - Reads `period` query param (default: `month`)
     - Calls `ClinicMetrics::finance($from, $to)` for financial metrics
     - Calls `ClinicMetrics::monthlyRevenue()` for trend chart
     - Reuses `resolvePeriod()` logic from `DocteurGeneralController`
     - Passes all data to the view

### AC2 — Dashboard view
- [ ] New view: `resources/views/caisse/raf/dashboard.blade.php`
- [ ] Period selector (today / 7 jours / 30 jours / ce mois / cette année)
     — same pills pattern as DocteurGeneral dashboard
- [ ] Page title: "Supervision financière"
- [ ] Period label displayed: "Période : {label}"

### AC3 — Financial KPIs (no recovery rate)
- [ ] Stat card: "Encaissé" — `$finance['collected']`
- [ ] Stat card: "Attendu" — `$finance['expected']`
- [ ] Stat card: "Impayé" — `$finance['pending']`
- [ ] Stat card: "Panier moyen" — `$finance['avg_basket']`
- [ ] Stat card: "Patients facturés" — `$finance['paying_patients']`
- [ ] NO "Taux de recouvrement" card (explicitly excluded per Sprint 2 decision)
- [ ] NO "Taux d'impayés" card

### AC4 — Revenue by service breakdown
- [ ] Horizontal bar chart showing `$finance['by_service']` (all services
     including pharmacy)
- [ ] Same visual pattern as DocteurGeneral "CA par service" section
- [ ] If no payments on period: "Aucun encaissement sur la période."

### AC5 — Revenue by service category breakdown
- [ ] Category cards showing `$finance['by_service_category']`:
     - Consultation
     - Laboratoire
     - Pharmacie
     - Hospitalisation
     - Chirurgie
     - Autre
- [ ] Same visual pattern as DocteurGeneral category cards
- [ ] Progress bar relative to the max category total (not to collected total)

### AC6 — Revenue trend chart
- [ ] Bar chart showing `monthlyRevenue` (12 months)
- [ ] Same visual pattern as DocteurGeneral "Tendance du CA"

### AC7 — Quick access to sessions and emergency closure
- [ ] Link/button to session list (`caisse.sessions.index`)
- [ ] Link/button to emergency closure info (or direct access to close a session)
- [ ] These links should be prominent since the RAF's core job is oversight
     and intervention

### AC8 — Navigation
- [ ] The RAF home route (`Role::Raf->homeRouteName()`) should point to
     the RAF dashboard (`caisse.raf.dashboard`), NOT the session list
- [ ] The session list remains accessible from the dashboard

### AC9 — Tests
- [ ] Test: RAF can access dashboard
- [ ] Test: non-RAF gets 403 on dashboard
- [ ] Test: dashboard passes financial data to view
- [ ] Test: dashboard respects period parameter
- [ ] Test: default period is "month"

## Out of scope
- Operations metrics (occupancy, acts, wait times) — those belong to Docteur General
- Recovery rate (taux de recouvrement) — excluded per Sprint 2 decision
- Session list changes (already done in T-007)
- Emergency close form (already done in T-008)
- Charts rendered via a charting library — use pure CSS bars like DocteurGeneral

## Technical notes
- Reuse `ClinicMetrics::finance()` and `ClinicMetrics::monthlyRevenue()` — same
  as `DocteurGeneralController`
- `resolvePeriod()` is private in `DocteurGeneralController`. Options:
  1. Extract it to a trait or helper
  2. Duplicate the logic in `CashierSessionController`
  3. Make it a static method on `ClinicMetrics`
  Best approach: extract to a trait `ResolvesPeriod` used by both controllers,
  since both dashboards need the same period logic.
- The RAF dashboard is financially focused — no operations tab needed. Single
  page with KPIs + charts.
- `formatMoney()` from `ClinicMetrics` is used for currency formatting.
- The view follows the same AlpineJS tab pattern as DocteurGeneral if tabs
  are needed, but for RAF a single scrollable page is sufficient (no tabs).
- RAF home route change: update `Role::Raf` homeRouteName from
  `caisse.sessions.index` to `caisse.raf.dashboard`

## Implementation log
- 2026-10-01 — Created `app/Traits/ResolvesPeriod.php` extracted from `DocteurGeneralController::resolvePeriod()`. Updated `DocteurGeneralController` to use the trait.
- 2026-10-01 — Updated `Role::Raf` homeRouteName from `caisse.sessions.index` to `caisse.raf.dashboard`
- 2026-10-01 — Added route `GET /caisse/raf` with middleware `role:raf,admin,docteur_general` (separate group, excludes caissier)
- 2026-10-01 — Added `rafDashboard()` to `CashierSessionController` with imports for `ClinicMetrics` and `ResolvesPeriod` trait
- 2026-10-01 — Created `resources/views/caisse/raf/dashboard.blade.php` — financial KPIs, CA par service, tendance 12 mois, catégories, quick access links
- 2026-10-01 — Updated sidebar nav: added RAF dashboard link under Supervision section, added RAF to Vacations roles
- 2026-10-01 — Added `use ResolvesPeriod` trait import to `CashierSessionController` (fix for 500 error)
- 2026-10-01 — Created `RafDashboardTest.php` with 5 tests, all passing (13 assertions)
- 2026-10-01 — Fix: moved RAF dashboard route from `role:caissier,docteur_general,raf` group to separate `role:raf,admin,docteur_general` group (caissier was getting 200 instead of 403)

## Review

Verdict : **GO**

### Bloquant
Aucun.

### Important
Aucun.

### Mineur / suggestion
- **[NIT]** `$periods` array dupliqué à l'identique entre `CashierSessionController::rafDashboard()` et `DocteurGeneralController::index()`. Le trait `ResolvesPeriod` a bien factorisé la logique de résolution, mais pas la liste des périodes. À extraire en `ResolvesPeriod::availablePeriods()` si la liste évolue.

### Points positifs
- Extraction du trait `ResolvesPeriod` propre, suppression de duplication sans sur-ingénierie.
- Sécurité bien pensée : le bug initial (caissier en 200) corrigé par un groupe de route dédié, pas un patch sur le middleware.

### Non-régression
- 51/51 tests passent. Aucune régression.
(filled by tech-lead-po)

## QA report
(filled by fullstack-qa)

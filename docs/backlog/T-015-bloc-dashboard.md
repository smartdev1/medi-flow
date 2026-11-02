---
id: T-015
title: Surgery list dashboard (bloc view)
status: todo
owner: fullstack-devops
author_of_code:
priority: P1
size: S
depends_on: T-013
phase: 2
---

## Context
The bloc opératoire needs a dashboard showing scheduled and in-progress surgeries. This is the operational view for the surgical team.

## User story
As a médecin or aide-soignante at the bloc, I want to see today's surgery schedule and each intervention's status, so that I can prepare rooms and coordinate the team.

## Acceptance criteria

### AC1 — BlocController
- [ ] `index()` — returns view with surgeries for selected date (default: today)
  - Eager loads: patient, surgeon, anesthetist, visit
  - Ordered by `scheduled_at`
  - Filterable by status and date

### AC2 — Route
- [ ] `GET /bloc` → BlocController@index
- [ ] Protected by `role:medecin,aide_soignante`

### AC3 — Vue
- [ ] `resources/views/bloc/dashboard.blade.php`
- [ ] Date picker to navigate days
- [ ] Table/cards with columns: heure, patient, intervention, chirurgien, anesthésiste, statut
- [ ] Status badges: programmée (blue), consentement signé (green), en cours (orange), terminée (gray), annulée (red)
- [ ] Click on a surgery opens detail (inline expand or link)

### AC4 — Navigation
- [ ] Add "Bloc opératoire" to the sidebar for medecin and aide_soignante roles

### AC5 — Tests
- [ ] Test: bloc dashboard loads for medecin
- [ ] Test: bloc dashboard loads for aide_soignante
- [ ] Test: non-authorized role cannot access
- [ ] Test: only surgeries for selected date are shown

## Out of scope
- Weekly/monthly planning view
- Room/salle assignment for surgeries
- Real-time updates (polling or websockets)

## Technical notes
- Reuse the existing layout pattern from medecin/dashboard and hospitalisation/dashboard.
- No new model — reads from Surgery (T-012).

---
id: T-019
title: Recovery room tracking (salle de réveil)
status: todo
owner: fullstack-devops
author_of_code:
priority: P1
size: M
depends_on: T-013
phase: 3
---

## Context
After surgery, the patient is transferred to the recovery room (salle de réveil) for post-anesthetic monitoring. This is a short stay (30min to 2h) tracked on the Surgery record, not as an Admission.

## User story
As an aide-soignante in the recovery room, I want to track when a patient enters and leaves recovery, with monitoring notes, so that post-anesthetic surveillance is documented.

## Acceptance criteria

### AC1 — Migration
- [ ] Add to `surgeries` table:
  - `recovery_started_at` (datetime, nullable)
  - `recovery_ended_at` (datetime, nullable)
  - `recovery_notes` (text, nullable)
  - `recovery_nurse_id` (FK users, nullable)

### AC2 — Controller
- [ ] `POST /surgeries/{surgery}/recovery/start` → SurgeryController@startRecovery
  - Sets recovery_started_at, recovery_nurse_id
  - Only when surgery status is `completed`
- [ ] `POST /surgeries/{surgery}/recovery/end` → SurgeryController@endRecovery
  - Sets recovery_ended_at
  - Validates recovery_notes (required, min 10 chars)
  - Only when recovery_started_at is set and recovery_ended_at is null

### AC3 — UI
- [ ] Recovery section on surgery detail view
- [ ] "Début surveillance" button (sets start time)
- [ ] Timer showing elapsed time since recovery start
- [ ] Notes textarea + "Fin surveillance" button
- [ ] After end, display summary: duration, nurse, notes (read-only)

### AC4 — Bloc dashboard integration
- [ ] Surgeries in recovery show status "En réveil" with elapsed time
- [ ] Color coding: orange while in recovery, green when released

### AC5 — Tests
- [ ] Test: start recovery sets timestamp and nurse
- [ ] Test: end recovery requires notes
- [ ] Test: cannot start recovery before surgery completed
- [ ] Test: cannot end recovery before starting it
- [ ] Test: duration calculation is correct

## Out of scope
- Vital signs monitoring (constantes) in recovery — use existing vitals flow if needed
- Aldrete score or other recovery scoring systems
- Recovery room capacity management

## Technical notes
- Recovery is NOT an Admission. No room, no bed, no daily billing. It's a short surveillance period tracked directly on Surgery.
- The recovery nurse is typically aide_soignante role.

---
id: T-013
title: Surgery scheduling UI (medecin dashboard)
status: todo
owner: fullstack-devops
author_of_code:
priority: P1
size: M
depends_on: T-012
phase: 2
---

## Context
The médecin needs to schedule a surgery from the consultation view. This task adds the UI and controller logic for creating and updating Surgery records.

## User story
As a médecin, I want to schedule a surgery for a patient during consultation, specifying the procedure, date, and anesthetist, so that the intervention is planned and visible to the bloc.

## Acceptance criteria

### AC1 — SurgeryController
- [ ] `store(Request, Visit)` — creates a Surgery linked to the visit
  - Validates: procedure_name (required), scheduled_at (nullable datetime), anesthetist_id (nullable, exists in users with role medecin)
  - Copies `surgery_type` from visit
  - Sets `surgeon_id` to authenticated user
  - Sets status to `scheduled`
- [ ] `update(Request, Surgery)` — updates schedule, anesthetist, notes
  - Only allowed while status is `scheduled` or `consent_signed`
- [ ] `destroy(Surgery)` — cancels (sets status to `cancelled`, does not delete)
  - Only allowed while status is `scheduled`

### AC2 — Routes
- [ ] `POST /medecin/visites/{visit}/surgery` → store
- [ ] `PATCH /surgeries/{surgery}` → update
- [ ] `DELETE /surgeries/{surgery}` → destroy (cancel)
- [ ] Routes protected by `role:medecin`

### AC3 — Medecin dashboard integration
- [ ] On the consultation view, add a "Programmer une chirurgie" button (visible when `visit->surgery_type` is not null)
- [ ] Modal or inline form with fields: procedure_name, scheduled_at, anesthetist_id (dropdown of medecin users)
- [ ] Display existing surgeries on the visit with status badge

### AC4 — Tests
- [ ] Test: medecin can create surgery on a visit with surgery_type
- [ ] Test: medecin cannot create surgery on a visit without surgery_type
- [ ] Test: update changes schedule and anesthetist
- [ ] Test: cancel sets status to cancelled
- [ ] Test: non-medecin cannot access routes

## Out of scope
- Bloc dashboard (T-015)
- Consent signing (T-014)
- Billing (T-016)

## Technical notes
- Anesthetist dropdown filters `User::where('role', Role::Medecin)`. No specialty field yet.
- Use Alpine.js for conditional display of the surgery form.

---
id: T-012
title: Surgery model and migration
status: todo
owner: fullstack-devops
author_of_code:
priority: P1
size: S
depends_on: T-010
phase: 2
---

## Context
Phase 2 of the surgical workflow. After T-010 introduces `surgery_type` on visits, we need a dedicated `Surgery` model to track scheduled interventions, assigned staff, and consent.

## User story
As a médecin, I want a Surgery record linked to a visit so that I can schedule an intervention, assign an anesthetist, and track its lifecycle.

## Acceptance criteria

### AC1 — Migration
- [ ] Create `surgeries` table with columns:
  - `id` (bigIncrements)
  - `visit_id` (FK visits, constrained)
  - `patient_id` (FK patients, constrained)
  - `surgeon_id` (FK users, constrained)
  - `anesthetist_id` (FK users, nullable)
  - `scheduled_at` (datetime, nullable)
  - `status` (string, default 'scheduled') — values: scheduled, consent_signed, in_progress, completed, cancelled
  - `surgery_type` (string) — interne/externe, copied from visit
  - `procedure_name` (string)
  - `pre_anesthetic_notes` (text, nullable)
  - `consent_signed_at` (datetime, nullable)
  - `consent_signed_by` (string, nullable)
  - `notes` (text, nullable)
  - `started_at` (datetime, nullable)
  - `completed_at` (datetime, nullable)
  - `timestamps`
- [ ] Migration is reversible

### AC2 — Model
- [ ] `Surgery` model with fillable, casts, and relationships:
  - `belongsTo(Visit)`, `belongsTo(Patient)`, `belongsTo(User, 'surgeon_id')`, `belongsTo(User, 'anesthetist_id')`
- [ ] `Visit` has `hasMany(Surgery::class)`
- [ ] `Patient` has `hasMany(Surgery::class)`
- [ ] Status constants defined on the model

### AC3 — Factory
- [ ] `SurgeryFactory` with sensible defaults

### AC4 — Tests
- [ ] Test: migration creates table with all columns
- [ ] Test: relationships work (visit, patient, surgeon, anesthetist)
- [ ] Test: factory produces valid model

## Out of scope
- UI (T-013)
- Consent signing flow (T-014)
- Billing integration (T-016)

## Technical notes
- Use `hasMany` on Visit (not `hasOne`) to support multiple interventions per visit.
- `surgery_type` is copied from `visit->surgery_type` at creation time for autonomy — the Surgery record should be self-contained.
- No new Role enum value. Surgeon and anesthetist are both `medecin` role users.

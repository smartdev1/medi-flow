---
id: T-017
title: WHO surgical safety checklist
status: todo
owner: fullstack-devops
author_of_code:
priority: P1
size: M
depends_on: T-013
phase: 3
---

## Context
The WHO Surgical Safety Checklist is a mandatory safety tool with 3 phases: Sign In (before anesthesia), Time Out (before incision), Sign Out (before patient leaves operating room). This task implements it digitally.

## User story
As a chirurgien, I want to complete the 3-phase safety checklist before and after each intervention, so that we comply with WHO standards and reduce surgical errors.

## Acceptance criteria

### AC1 — Migration
- [ ] Create `surgery_checklists` table:
  - `id`, `surgery_id` (FK), `phase` (string: sign_in, time_out, sign_out)
  - `checked_by` (FK users), `checked_at` (datetime)
  - `items` (JSON — key-value pairs of checklist items and responses)
  - `notes` (text, nullable), `timestamps`
- [ ] Unique constraint on (surgery_id, phase) — one record per phase per surgery

### AC2 — Model
- [ ] `SurgeryChecklist` model with relationships
- [ ] `Surgery hasMany SurgeryChecklist`
- [ ] Scopes: `forPhase($phase)`

### AC3 — Controller
- [ ] `POST /surgeries/{surgery}/checklist/{phase}` → SurgeryChecklistController@store
- [ ] Validates phase is one of sign_in, time_out, sign_out
- [ ] Validates items as required JSON
- [ ] Cannot submit if phase already completed (idempotency)
- [ ] Sign In required before surgery can transition to `in_progress`
- [ ] Sign Out required before surgery can transition to `completed`

### AC4 — UI
- [ ] Checklist form in bloc dashboard, per surgery
- [ ] 3 tabs/sections for each phase
- [ ] Each phase shows its items as checkboxes with labels
- [ ] Completed phases shown as read-only with green checkmark

### AC5 — Default checklist items
- [ ] Sign In: patient identity confirmed, site marked, anesthesia check, allergies verified, airway risk assessed, blood loss risk assessed
- [ ] Time Out: team introduction, patient/procedure/site confirmed, antibiotics given, imaging displayed
- [ ] Sign Out: procedure name confirmed, instrument/sponge count, specimen labelled, equipment issues, post-op plan communicated

### AC6 — Tests
- [ ] Test: checklist creation for each phase
- [ ] Test: duplicate phase is rejected
- [ ] Test: surgery cannot start without Sign In
- [ ] Test: surgery cannot complete without Sign Out

## Out of scope
- Custom checklist items per clinic (hardcoded WHO standard for now)
- PDF export of completed checklist

## Technical notes
- Checklist items are stored as JSON for flexibility. The default items are defined in a config file or constant, not in the database.
- Validate with the clinic (Clinique Saint-Matthieu) that the WHO standard items match their practice before deployment.

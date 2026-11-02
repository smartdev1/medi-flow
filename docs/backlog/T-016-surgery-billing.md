---
id: T-016
title: Surgery billing integration
status: todo
owner: fullstack-devops
author_of_code:
priority: P1
size: S
depends_on: T-012
phase: 2
---

## Context
When a surgery is completed, its associated acts (surgical act, anesthesia) must appear on the visit's invoice. This task integrates Surgery with the existing Invoice::forVisit / recalculate flow.

## User story
As a caissier, I want completed surgeries to automatically appear on the patient's invoice, so that billing is accurate without manual entry.

## Acceptance criteria

### AC1 — Invoice::recalculate integration
- [ ] When a Surgery with status `completed` exists on the visit:
  - The surgical act (from visit's `act_id`) is included in the breakdown (interne) or replaced by bloc-location act (externe) — T-010 logic preserved
  - If `anesthetist_id` is set, an anesthesia act line is added to the breakdown
- [ ] Anesthesia act: use a catalog act with category `anesthesie` (create in seeder if missing)
- [ ] Multiple surgeries on same visit: each adds its own lines

### AC2 — Seeder
- [ ] Ensure an act "Consultation anesthésique" exists in the acts catalog (category: `anesthesie`)
- [ ] Price set to clinic default (configurable via acts admin)

### AC3 — Invoice display
- [ ] Surgery lines appear in the invoice breakdown with label "Chirurgie — {procedure_name}" or "Anesthésie — {procedure_name}"
- [ ] Existing invoice views display these lines without modification (they already iterate the breakdown)

### AC4 — Tests
- [ ] Test: completed surgery adds surgical act to invoice
- [ ] Test: completed surgery with anesthetist adds anesthesia line
- [ ] Test: incomplete surgery does not appear on invoice
- [ ] Test: multiple surgeries on same visit are all billed

## Out of scope
- Anesthesia pricing tiers (flat rate for now)
- Consumables billing (handled via pharmacy flow — AC5 of T-010)

## Technical notes
- `Invoice::recalculate()` already iterates act categories. Add a surgery-specific section after the existing bloc/chirurgie logic.
- The anesthesia act category must be added to `ActCategory` enum if it doesn't exist.

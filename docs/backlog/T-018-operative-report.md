---
id: T-018
title: Operative report (compte rendu opératoire)
status: todo
owner: fullstack-devops
author_of_code:
priority: P1
size: M
depends_on: T-013
phase: 3
---

## Context
After each intervention, the surgeon must produce an operative report documenting the procedure performed, findings, and post-operative instructions. This is a medico-legal requirement.

## User story
As a chirurgien, I want to write and save the operative report after completing an intervention, so that the procedure is documented for the patient's medical record.

## Acceptance criteria

### AC1 — Migration
- [ ] Add to `surgeries` table:
  - `operative_report` (text, nullable)
  - `operative_report_by` (FK users, nullable)
  - `operative_report_at` (datetime, nullable)

### AC2 — Controller
- [ ] `POST /surgeries/{surgery}/report` → SurgeryController@storeReport
- [ ] Only allowed when surgery status is `completed`
- [ ] Sets operative_report, operative_report_by (auth user), operative_report_at (now)
- [ ] Validation: operative_report required, min 50 characters

### AC3 — UI
- [ ] On surgery detail view, "Compte rendu opératoire" section
- [ ] Rich text area (or plain textarea) for the report
- [ ] After submission, display as read-only with author and date
- [ ] Print/PDF button for the operative report

### AC4 — PDF generation
- [ ] Use existing dompdf/spatie-pdf setup
- [ ] Template: clinic header, patient info, procedure details, report body, surgeon signature line

### AC5 — Tests
- [ ] Test: surgeon can submit operative report after completion
- [ ] Test: report blocked before surgery is completed
- [ ] Test: report fields are persisted
- [ ] Test: PDF generates without error

## Out of scope
- Structured operative report (templates per procedure type)
- Digital signature on the report
- Report versioning

## Technical notes
- Reuse the existing PDF generation pattern from invoice receipts.
- The operative report is part of the patient's legal medical record.

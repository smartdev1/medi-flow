---
id: T-020
title: Surgery audit log
status: todo
owner: fullstack-devops
author_of_code:
priority: P2
size: S
depends_on: T-017, T-018
phase: 3
---

## Context
Surgical interventions are high-risk medical acts requiring full traceability. Every status change, checklist completion, and report submission must be logged for medico-legal compliance. This is the first audit log in the system — scoped to surgery only, but designed to be extended later.

## User story
As a docteur_general, I want an audit trail of all actions taken on a surgery, so that I can review who did what and when for compliance and incident investigation.

## Acceptance criteria

### AC1 — Migration
- [ ] Create `audit_logs` table:
  - `id`, `auditable_type` (string), `auditable_id` (unsignedBigInt)
  - `user_id` (FK users, nullable — system actions)
  - `action` (string: created, updated, status_changed, checklist_completed, consent_signed, report_submitted, recovery_started, recovery_ended)
  - `old_values` (JSON, nullable)
  - `new_values` (JSON, nullable)
  - `ip_address` (string, nullable)
  - `timestamps`
- [ ] Index on (auditable_type, auditable_id)

### AC2 — Model
- [ ] `AuditLog` model, polymorphic relationship
- [ ] Trait `Auditable` that can be used on any model (applied to Surgery first)

### AC3 — Logging
- [ ] Every Surgery status transition creates an audit log entry
- [ ] Checklist completion creates an entry
- [ ] Consent signing creates an entry
- [ ] Operative report submission creates an entry
- [ ] Recovery start/end creates entries

### AC4 — Display
- [ ] On surgery detail view, "Historique" section showing audit trail
- [ ] Each entry: date, user, action, details
- [ ] Accessible to medecin and docteur_general

### AC5 — Tests
- [ ] Test: status change creates audit entry
- [ ] Test: audit log records old and new values
- [ ] Test: audit log records acting user

## Out of scope
- Audit log for non-surgery models (payments, admissions, etc.) — future extension
- Tamper-proof log (blockchain, append-only) — overkill for a clinic SIH
- Audit log dashboard with filters and export

## Technical notes
- The `Auditable` trait is designed for reuse. Other models (Payment, Admission, Visit) can adopt it later to address TD-004.
- Use polymorphic morphMany relationship for flexibility.
- Do NOT use Laravel model events for audit logging yet — keep it explicit in controllers for clarity. Events/Listeners come in T-021.

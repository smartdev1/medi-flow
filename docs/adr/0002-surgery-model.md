# ADR 0002 — Dedicated Surgery model

## Status
Accepted

## Date
2026-10-31

## Context
The clinic needs to manage surgical interventions: scheduling, consent, safety checklists, operative reports, and recovery tracking. The question is whether to add these fields to the existing Visit or Admission models, or create a dedicated Surgery model.

Visit already carries 13+ fields and relationships (patient, act, consultation, exams, prescriptions, payments, invoice, admission). Adding surgeon_id, anesthetist_id, scheduled_at, consent fields, operative report, and recovery tracking would turn it into a God Model.

Admission manages the physical stay (room, bed, daily billing via AdmissionDayLine). A surgery is an operative act, not a stay — they are orthogonal concepts.

## Decision
Create a dedicated `Surgery` model with `belongsTo(Visit)`.

Key design choices:
- **`hasMany` on Visit**, not `hasOne` — supports multiple interventions per visit at zero cost.
- **`surgery_type` copied from Visit** — the Surgery record is self-contained; it doesn't need to join back to Visit to know if it's interne/externe.
- **No new Role** for anesthésiste — an anesthésiste is a médecin with a specialty. Roles control system access; specialties are a domain attribute. A `specialty` field on `users` may come later (phase 3) if filtering becomes necessary.
- **Lifecycle on Surgery, not Visit** — Surgery has its own status flow (scheduled → consent_signed → in_progress → completed → cancelled) independent of Visit's status flow.

## Consequences
- New table `surgeries` with ~15 columns (phase 2), extended with operative report and recovery fields in phase 3.
- New table `surgery_checklists` for WHO safety checklist (phase 3).
- Invoice::recalculate() reads Surgery records to determine billing lines.
- Surgery becomes the first model to use the `Auditable` trait (phase 3), paving the way for system-wide audit logging.

## Alternatives considered
1. **Enrich Visit** — rejected: God Model risk, mixed lifecycle concerns.
2. **Enrich Admission** — rejected: surgery ≠ stay, and not all surgeries require admission (outpatient/ambulatory).
3. **Surgery as a type of Consultation** — rejected: consultations are diagnostic, surgeries are therapeutic. Different lifecycles, different actors.

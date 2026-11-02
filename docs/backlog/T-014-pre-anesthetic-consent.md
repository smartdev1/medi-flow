---
id: T-014
title: Pre-anesthetic consultation and consent
status: todo
owner: fullstack-devops
author_of_code:
priority: P1
size: S
depends_on: T-012
phase: 2
---

## Context
Before any intervention, the anesthetist evaluates the patient and the patient signs an informed consent. This task adds the consultation préanesthésique and consent signing flow.

## User story
As an anesthésiste (médecin), I want to record my pre-anesthetic evaluation and capture the patient's consent, so that the intervention can proceed with proper authorization.

## Acceptance criteria

### AC1 — Pre-anesthetic consultation
- [ ] On a Surgery record, the assigned anesthetist can fill `pre_anesthetic_notes` (text area)
- [ ] Route: `PATCH /surgeries/{surgery}/pre-anesthetic` → SurgeryController@preAnesthetic
- [ ] Only the assigned anesthetist (or any medecin if no anesthetist assigned) can submit
- [ ] Validation: pre_anesthetic_notes required, min 10 characters

### AC2 — Consent signing
- [ ] Route: `POST /surgeries/{surgery}/consent` → SurgeryController@signConsent
- [ ] Sets `consent_signed_at` to now, `consent_signed_by` to provided name
- [ ] Transitions status from `scheduled` to `consent_signed`
- [ ] Validation: consent_signed_by required (patient or guardian name)
- [ ] Cannot sign consent if pre_anesthetic_notes is empty (safety gate)

### AC3 — UI
- [ ] On the surgery detail view, display pre-anesthetic section with text area
- [ ] Consent section: input for signatory name + "Signer le consentement" button
- [ ] Consent button disabled until pre_anesthetic_notes is filled
- [ ] After signing, display signed status with date and signatory name (read-only)

### AC4 — Tests
- [ ] Test: anesthetist can fill pre-anesthetic notes
- [ ] Test: consent signing transitions status
- [ ] Test: consent blocked without pre-anesthetic notes
- [ ] Test: consent_signed_by is recorded

## Out of scope
- Electronic signature (PDF, biometric) — future iteration
- Consent revocation workflow
- Pre-anesthetic checklist items (free text for now)

## Technical notes
- Consent is a medico-legal document. The `consent_signed_at` + `consent_signed_by` pair is the minimum viable trace.
- No new model: all fields already exist on Surgery (T-012).

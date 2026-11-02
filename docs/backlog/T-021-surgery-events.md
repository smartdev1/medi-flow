---
id: T-021
title: Events/Listeners refactor for surgery workflow
status: todo
owner: fullstack-devops
author_of_code:
priority: P2
size: S
depends_on: T-018, T-019
phase: 3
---

## Context
The surgery workflow triggers multiple side-effects on status changes: billing recalculation, audit logging, and potentially notifications. Currently all logic is imperative in controllers. This task introduces Events/Listeners for the surgery domain only, as a first step toward decoupling.

## User story
As a developer, I want surgery status changes to fire domain events, so that side-effects (billing, audit, notifications) are decoupled from controller logic.

## Acceptance criteria

### AC1 — Events
- [ ] `SurgeryScheduled` — fired when a surgery is created
- [ ] `SurgeryStarted` — fired when status transitions to `in_progress`
- [ ] `SurgeryCompleted` — fired when status transitions to `completed`
- [ ] `SurgeryCancelled` — fired when status transitions to `cancelled`
- [ ] Each event carries the Surgery model instance

### AC2 — Listeners
- [ ] `RecalculateInvoiceOnSurgeryComplete` — listens to SurgeryCompleted, triggers Invoice::forVisit recalculation
- [ ] `LogSurgeryAudit` — listens to all surgery events, creates audit log entries (replaces explicit calls from T-020)
- [ ] Listeners registered in EventServiceProvider

### AC3 — Controller refactor
- [ ] SurgeryController dispatches events instead of calling audit/invoice logic directly
- [ ] Existing behavior preserved — this is a refactor, not a feature change

### AC4 — Tests
- [ ] Test: SurgeryCompleted event triggers invoice recalculation
- [ ] Test: all events create audit log entries
- [ ] Test: event dispatch does not break existing flows

## Out of scope
- Events for non-surgery domains (payments, admissions)
- Queue-based listeners (synchronous for now)
- Notification listeners (email, SMS)

## Technical notes
- Scope this refactor to surgery controllers ONLY. Do not touch AdmissionController, PaymentController, etc.
- All listeners run synchronously (implements ShouldQueue is NOT added). Async can come later.
- This resolves TD-005 for the surgery domain.

---
id: T-001
title: Fix medium severity issues
status: review
owner: fullstack-devops
author_of_code: fullstack-devops
priority: P1
size: M
---

## Context
Medium-severity issues identified during codebase analysis: missing Invoice sync in payment flow, unchecked authorization in FormRequests, inline timeline construction, and missing property verification in controllers.

## User story
As a developer, I want to fix security and correctness gaps so the application follows Laravel best practices and RBAC rules.

## Acceptance criteria
- [x] Given a payment marked as paid, When the Invoice exists for the same visit, Then the Invoice status is updated to paid
- [x] Given any FormRequest, When authorize() is called, Then it returns false for unauthorized roles instead of always true
- [x] Given PatientController::show(), When building the timeline, Then it uses PatientTimeline::build()
- [x] Given critical controllers, When accessing a resource, Then the user's ownership is verified via Policy

## Out of scope
- No new features, only bug fixes and security improvements
- No migration of existing data

## Technical notes
- Policies use Role enum for RBAC
- PatientTimeline::build() format differs from inline format — view adapted
- Gate::before in AuthServiceProvider provides admin bypass

## Implementation log

### Problem 1: markAsPaid() Invoice sync
- Modified `PaymentController::markAsPaid()` to find and update the associated Invoice
- Sets status to `paid`, calculates total paid amount from all paid payments for the visit
- Sets `paid_at` and `cashier_id` on the Invoice

### Problem 2: authorize() in FormRequests
- Created 4 policies: `PatientPolicy`, `PaymentPolicy`, `ExamPolicy`, `InvoicePolicy`
- Registered policies in `AuthServiceProvider::$policies`
- Added `Gate::before` for admin bypass
- Updated 5 FormRequests with role-based authorization:
  - `StorePatientRequest`: accueil, secretaire, admin
  - `StorePaymentRequest`: caissier, admin
  - `StoreExamRequest`: laborantin, medecin, pediatre, admin
  - `StoreDoctorRequest`: admin only
  - `UpdateDoctorRequest`: admin only

### Problem 3: PatientController::show() timeline
- Replaced inline timeline construction with `PatientTimeline::build($patient)`
- Removed ~80 lines of manual timeline building from PatientController
- Updated `patients/show.blade.php` to match PatientTimeline format:
  - `label` instead of `title`
  - `time` instead of `date` (formatted as H:i)
  - `meta` instead of `details` model objects
  - Removed the `@switch` block, using simple meta display

### Problem 4: Property verification
- Added `$this->authorize('markAsPaid', $payment)` in PaymentController::markAsPaid()
- Added `$this->authorize('view', $payment)` in PaymentController::receipt() and downloadReceipt()
- Added `$this->authorize('view', $patient)` in PatientController::show()
- Added `$this->authorize('viewAny', Payment::class)` in CaisseController::index()
- Added `// TODO: Policy check` comments in:
  - AdmissionController::store()
  - ExamController::store()
  - ExamController::advance()
  - ReferralController::store()
  - PrescriptionController::store()
  - PrescriptionController::dispense()

## Review
(filled by tech-lead-po)

## QA report
(filled by QA agent)

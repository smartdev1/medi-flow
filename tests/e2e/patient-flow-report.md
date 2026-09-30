# Patient Flow E2E Test Report

**Date**: 2026-09-29T19:33:11.201Z
**Server**: `http://localhost:8000`
**Accounts**: `dr_gnle@cmsm.ci` (docteur_general) | `aide@cmsm.ci` (aide_soignante)

## Executive Summary

| Metric | Value |
|---|---|
| Total steps executed | 11 |
| PASS | 3 |
| SKIPPED | 0 |
| UNKNOWN | 8 |
| ERRORS | 0 |
| Bugs found | 0 |
| Network failures | 0 |
| JS errors | 0 |

## Verdict per Step

| # | Step | User | Status | Detail |
|---|---|---|---|---|
| 1 | `LOGIN` | both | ✅ PASS |  |
| 2 | `REGISTER_PATIENT` | doctor | ❓ UNKNOWN | code=? |
| 3 | `TAKE_VITALS` | nurse | ❓ UNKNOWN | visit=3 |
| 4 | `TAKE_VISIT` | nurse | ❓ UNKNOWN | visit=3 |
| 5 | `REQUEST_HOSPITALIZATION` | doctor | ❓ UNKNOWN | visit=? |
| 6 | `APPROVE_ADMISSION` | nurse | ❓ UNKNOWN | request=1 |
| 7 | `CLOSE_CONSULTATION` | doctor | ❓ UNKNOWN | consultation=? |
| 8 | `DISCHARGE` | doctor | ❓ UNKNOWN | visit=? |
| 9 | `PROCESS_PAYMENT` | doctor | ❓ UNKNOWN | invoice=? |
| 10 | `CAISSE_ACCESS` | doctor | ✅ PASS |  |
| 11 | `POSTCHECK_DISCHARGE_VISIBLE` | doctor | ✅ PASS |  |

## Screenshots

- `2026-09-29T19-31-54_00_doctor_logged.png`
- `2026-09-29T19-31-55_00_nurse_logged.png`
- `2026-09-29T19-31-58_01_accueil.png`
- `2026-09-29T19-32-00_01_slideover.png`
- `2026-09-29T19-32-04_01_after_registration.png`
- `2026-09-29T19-32-08_02_salle_attente.png`
- `2026-09-29T19-32-13_02_after_vitals.png`
- `2026-09-29T19-32-31_03_medecin_queue.png`
- `2026-09-29T19-32-35_03_after_hospitalization.png`
- `2026-09-29T19-32-37_04_admission_requests.png`
- `2026-09-29T19-32-41_04_after_approve.png`
- `2026-09-29T19-32-46_05_medecin_close.png`
- `2026-09-29T19-32-49_05_after_close.png`
- `2026-09-29T19-32-52_06_sortie.png`
- `2026-09-29T19-32-56_06_after_discharge.png`
- `2026-09-29T19-32-59_07_caisse.png`
- `2026-09-29T19-33-02_07_after_payment.png`
- `2026-09-29T19-33-05_08_sortie_final.png`
- `2026-09-29T19-33-10_08_salle_final.png`

> Full JSON: `tests/e2e/patient-flow-report.json`
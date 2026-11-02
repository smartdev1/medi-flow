# Tech Debt

## Tracked

| ID | Description | Task | Impact | Planned removal |
|---|---|---|---|---|
| TD-001 | `closeDay()` in PaymentController deprecated, kept for backward compat | T-002 | Low — still works, redirects to session close | After T-002 validation + 1 sprint |
| TD-002 | Payments without `cashier_session_id` after backfill | T-002 | Low — nullable column, valid but not ideal | After backfill verification |
| TD-003 | No cash denomination breakdown (only total balance) | — | Medium — caissier must count manually | Future iteration |
| TD-004 | No audit log — medico-legal risk for surgical and financial actions | T-020 | High — compliance policy requires it (section 12) | Phase 3 (surgery audit first, then extend) |
| TD-005 | No Events/Listeners — controller logic tightly coupled to side-effects | T-021 | Medium — manageable now, blocks scalability | Phase 3 (surgery domain first) |

# 0001 — CashierSession model for shift-based cash management
Status: accepted
Date: 2026-09-28

## Context
The caisse module currently closes payments per day per cashier. The business
requires multiple cashiers working in succession (rondes) on the same day.
There is no concept of "session" — no opening balance, no closing balance,
no cash variance tracking, no handover between caissiers. The current
"clôturer ma caisse" button closes open payments but does not close a
structured session.

This leads to:
- No accountability per round (qui a pris la caisse à 08:00, combien y avait-il, combien y a-t-il à 14:00 ?)
- No cash variance detection (écart de caisse)
- No way to see "who is currently on duty"
- No structured handover between caissiers

## Options considered

1. Keep current per-day close, rename UI labels
   - Pros: zero code change
   - Cons: no session lifecycle, no balance tracking, no variance, no audit trail.
     Doesn't solve the actual business problem.

2. CashierSession model (RECOMMENDED)
   - Pros: explicit session lifecycle, opening/closing balance, cash variance,
     clear audit trail, single active session enforcement, enables handover
     protocol. Aligns with "rondes" business concept.
   - Cons: new model + migration + views + controller changes. Moderate effort.

3. Per-day closing with enhanced UI (no new model)
   - Pros: less code
   - Cons: still no structured session, no balance tracking, no variance.
     Partial improvement only.

## Decision
Implement a CashierSession model (option 2).

## Consequences
- New `cashier_sessions` table + `cashier_session_id` column on `payments`
- New model, controller methods, views, policy
- Payment creation requires an active session (business rule enforcement)
- "closeDay" is deprecated in favor of "closeSession"
- Existing payments remain backward-compatible (nullable session_id initially)
- Backfill strategy needed for pre-existing data

## Revisit when
- Multi-device sessions required (one session per device, not per user)
- Cash denomination counting needed (5000 / 10000 / etc. breakdown)
- Integration with external accounting software

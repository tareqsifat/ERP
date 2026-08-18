# Costing Module

## What this solves

Per-order/style cost tracking — PRD v1 §3.3 ("Costing List / Costing
Form: Mirrors the budget structure for cost tracking per order and
style"). Structurally identical to Modules/Budgeting, tracking actual/
estimated cost instead of budgeted price.

## Main entities

- `App\Models\Costing` — `order_id`, `style`, `costed_quantity`,
  `average_unit_cost`, `total_cost` (server-computed:
  `costed_quantity * average_unit_cost`), `status`
  (`draft`/`approved`).

## API endpoints

| Method | Path | Purpose | Gate |
|---|---|---|---|
| GET | `/api/v1/costings` | Paginated, order/status-filterable costing list | `permission:costing.view` |
| GET | `/api/v1/costings/{costing}` | Single costing | `permission:costing.view` |
| POST | `/api/v1/costings` | Create a costing line | `permission:costing.create` |
| PUT | `/api/v1/costings/{costing}` | Update a costing line (recomputes total_cost if qty/cost change) | `permission:costing.edit` |
| DELETE | `/api/v1/costings/{costing}` | Soft-delete a costing line | `permission:costing.delete` |

## Depends on / depended on by

- Depends on: Order (`order_id`).
- Depended on by: Modules/Report (budget vs. actual cost comparisons,
  Phase 7).

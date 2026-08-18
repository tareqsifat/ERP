# Budgeting Module

## What this solves

Per-order/style budget tracking — PRD v1 §3.3 ("Budget List / Add New
Budget: Tracks per-order budgeted quantity, average unit price, and total
value against status").

## Main entities

- `App\Models\Budget` — `order_id`, `style`, `budgeted_quantity`,
  `average_unit_price`, `total_value` (server-computed:
  `budgeted_quantity * average_unit_price`), `status`
  (`draft`/`approved`).

## API endpoints

| Method | Path | Purpose | Gate |
|---|---|---|---|
| GET | `/api/v1/budgets` | Paginated, order/status-filterable budget list | `permission:budgeting.view` |
| GET | `/api/v1/budgets/{budget}` | Single budget | `permission:budgeting.view` |
| POST | `/api/v1/budgets` | Create a budget line | `permission:budgeting.create` |
| PUT | `/api/v1/budgets/{budget}` | Update a budget line (recomputes total_value if qty/price change) | `permission:budgeting.edit` |
| DELETE | `/api/v1/budgets/{budget}` | Soft-delete a budget line | `permission:budgeting.delete` |

## Depends on / depended on by

- Depends on: Order (`order_id`).
- Depended on by: Modules/Costing and Modules/Report mirror/compare
  against this later (budget vs. actual cost, budget vs. actual
  production).

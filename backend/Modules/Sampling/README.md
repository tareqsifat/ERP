# Sampling Module

## What this solves

Sample request tracking — PRD v1 §3.4 ("Sample List / Add New Sample:
Tracks sample requests per order including consignee, style number, item,
sample type, and garment quantity with status").

## Main entities

- `App\Models\Sample` — `order_id`, `consignee`, `style_number`, `item`,
  `sample_type`, `quantity`, `status`
  (`requested`/`sent`/`approved`/`rejected`).

## API endpoints

| Method | Path | Purpose | Gate |
|---|---|---|---|
| GET | `/api/v1/samples` | Paginated, order/status-filterable sample list | `permission:sampling.view` |
| GET | `/api/v1/samples/{sample}` | Single sample | `permission:sampling.view` |
| POST | `/api/v1/samples` | Create a sample request | `permission:sampling.create` |
| PUT | `/api/v1/samples/{sample}` | Update a sample (e.g. status) | `permission:sampling.edit` |
| DELETE | `/api/v1/samples/{sample}` | Soft-delete a sample record | `permission:sampling.delete` |

## Depends on / depended on by

- Depends on: Order (`order_id`).
- Depended on by: nothing yet.

## Deliberate default (not PRD-specified, documented here)

`sample_type` enum: `proto` / `fit` / `pp` (Pre-Production) / `size_set` /
`shipment` / `salesman` — standard garment-export sampling stage
terminology. The PRD names the field but doesn't enumerate values; if the
client uses different terms, this is a one-migration change — flag it
during the Phase 3 review.

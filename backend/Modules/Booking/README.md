# Booking Module

## What this solves

Booking specification — PRD v1 §3.2 / §4.4 / §6.2. A Booking always
references an existing Order and layers on fabric/spec detail (process
loss, composition, rib/collar) plus a line-item table mirroring the
order's styles with fabric-consumption fields the Order module doesn't
carry (DZN conversions, gray fabric/rib KG).

## Main entities

- `App\Models\Booking` — `order_id`, `preparer_id`, `booking_date`,
  `composition`, `process_loss_percent`, `other_fabrics`, `rib`, `collar`,
  `item_image_path`, `status`.
- `App\Models\BookingLineItem` — `style`, `color`, `shipment_date`,
  `quantity`, `unit_price`, `total_value` (server-computed), plus
  `garment_description`, `garment_picture_path`, `pantone`,
  `body_fabrication`, `yarn_count`, `dzn_quantity`,
  `gray_fabric_consumption_kg`, `rib_consumption_kg`.

## Line item update semantics

Same full-replace-on-`line_items`-present rule as Modules/Order — see
that module's README for the rationale.

## API endpoints

| Method | Path | Purpose | Gate |
|---|---|---|---|
| GET | `/api/v1/bookings` | Paginated, order/status-filterable booking list | `permission:booking.view` |
| GET | `/api/v1/bookings/{booking}` | Single booking with order, preparer, line items | `permission:booking.view` |
| POST | `/api/v1/bookings` | Create a booking + its line items | `permission:booking.create` |
| PUT | `/api/v1/bookings/{booking}` | Update header fields and/or replace line items | `permission:booking.edit` |
| DELETE | `/api/v1/bookings/{booking}` | Soft-delete a booking | `permission:booking.delete` |

## Depends on / depended on by

- Depends on: Order (`order_id`), User (`preparer_id`).
- Depended on by: nothing yet directly, but conceptually the spec Cutting
  (Phase 4, Modules/Production) will read from.

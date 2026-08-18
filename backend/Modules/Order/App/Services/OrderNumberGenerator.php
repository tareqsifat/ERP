<?php

namespace Modules\Order\App\Services;

use Modules\Order\App\Models\Order;

/**
 * PRD v1 §6.1: "Order No — Unique auto-generated identifier — Auto-
 * increment, e.g. 0000012". Derived from the row's own auto-increment
 * `id` (zero-padded to 7 digits) rather than a separate counter table —
 * this makes it inherently race-condition-free (no "read max, add one,
 * hope nobody else did the same in between" window) at the cost of the
 * number reflecting `id` gaps if a row is ever hard-deleted. Since orders
 * are soft-delete only in practice (sdd.md §5), that gap case doesn't
 * arise in normal operation.
 */
class OrderNumberGenerator
{
    public static function generateFor(Order $order): string
    {
        return str_pad((string) $order->id, 7, '0', STR_PAD_LEFT);
    }
}

<?php

use App\Models\User;
use Modules\Booking\App\Models\Booking;
use Modules\Order\App\Models\Order;

// sdd.md §6: smoke test hitting every route in Modules/Booking/routes/api.php,
// with at least one real assertion per core write endpoint.

test('admin can list bookings', function () {
    actingAsRole('Admin');
    Booking::factory()->count(2)->create();

    $this->getJson('/api/v1/bookings')->assertOk()->assertJsonCount(2, 'data');
});

test('creating a booking computes each line item total_value server-side', function () {
    actingAsRole('Admin');
    $order = Order::factory()->create();
    $preparer = User::factory()->create();

    $response = $this->postJson('/api/v1/bookings', [
        'order_id' => $order->id,
        'preparer_id' => $preparer->id,
        'booking_date' => now()->toDateString(),
        'composition' => '100% Cotton',
        'line_items' => [
            ['style' => 'ST-1', 'color' => 'Red', 'quantity' => 12, 'unit_price' => 3.25, 'total_value' => 999999],
        ],
    ]);

    $response->assertCreated();
    $this->assertDatabaseHas('booking_line_items', ['style' => 'ST-1', 'total_value' => 39.00]);
});

test('booking rejects a non-existent order', function () {
    actingAsRole('Admin');
    $preparer = User::factory()->create();

    $this->postJson('/api/v1/bookings', [
        'order_id' => 999999,
        'preparer_id' => $preparer->id,
        'booking_date' => now()->toDateString(),
        'line_items' => [
            ['style' => 'ST-1', 'color' => 'Red', 'quantity' => 1, 'unit_price' => 1],
        ],
    ])->assertStatus(422)->assertJsonValidationErrors('order_id');
});

test('a merchandiser can create a booking but a line supervisor cannot', function () {
    $order = Order::factory()->create();
    $preparer = User::factory()->create();
    $payload = [
        'order_id' => $order->id,
        'preparer_id' => $preparer->id,
        'booking_date' => now()->toDateString(),
        'line_items' => [
            ['style' => 'ST-1', 'color' => 'Red', 'quantity' => 1, 'unit_price' => 1],
        ],
    ];

    actingAsRole('Merchandiser');
    $this->postJson('/api/v1/bookings', $payload)->assertCreated();

    actingAsRole('Line Supervisor');
    $this->postJson('/api/v1/bookings', $payload)->assertStatus(403);
});

test('admin can soft-delete a booking', function () {
    actingAsRole('Admin');
    $booking = Booking::factory()->create();

    $this->deleteJson("/api/v1/bookings/{$booking->id}")->assertStatus(204);

    $this->assertSoftDeleted('bookings', ['id' => $booking->id]);
});

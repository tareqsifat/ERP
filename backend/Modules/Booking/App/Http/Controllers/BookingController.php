<?php

namespace Modules\Booking\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Booking\App\Http\Requests\StoreBookingRequest;
use Modules\Booking\App\Http\Requests\UpdateBookingRequest;
use Modules\Booking\App\Http\Resources\BookingResource;
use Modules\Booking\App\Models\Booking;

/**
 * PRD v1 §3.2 / §4.4 (Booking Management).
 */
class BookingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->integer('per_page', 20), 100);

        $bookings = Booking::query()
            ->with(['order', 'preparer'])
            ->when($request->filled('order_id'), fn ($q) => $q->where('order_id', $request->integer('order_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->orderByDesc('id')
            ->paginate($perPage);

        return $this->ok(BookingResource::collection($bookings));
    }

    public function show(Booking $booking): JsonResponse
    {
        $booking->load(['order', 'preparer', 'lineItems']);

        return $this->ok(new BookingResource($booking));
    }

    public function store(StoreBookingRequest $request): JsonResponse
    {
        $data = $request->validated();
        $lineItems = $data['line_items'];
        unset($data['line_items']);

        if ($request->hasFile('image')) {
            $data['item_image_path'] = $request->file('image')->store('bookings', 'local');
        }
        unset($data['image']);

        $booking = DB::transaction(function () use ($data, $lineItems) {
            $booking = Booking::create($data);

            foreach ($lineItems as $item) {
                $booking->lineItems()->create([
                    ...collect($item)->except(['quantity', 'unit_price'])->toArray(),
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_value' => round($item['quantity'] * $item['unit_price'], 2),
                ]);
            }

            return $booking;
        });

        $booking->load(['order', 'preparer', 'lineItems']);

        return $this->created(new BookingResource($booking));
    }

    public function update(UpdateBookingRequest $request, Booking $booking): JsonResponse
    {
        $data = $request->validated();
        $lineItems = $data['line_items'] ?? null;
        unset($data['line_items']);

        if ($request->hasFile('image')) {
            if ($booking->item_image_path) {
                Storage::disk('local')->delete($booking->item_image_path);
            }
            $data['item_image_path'] = $request->file('image')->store('bookings', 'local');
        }
        unset($data['image']);

        DB::transaction(function () use ($data, $lineItems, $booking) {
            $booking->fill($data);
            $booking->save();

            // Full-replace semantics, same rationale as Modules/Order —
            // see Order's README "Line item update semantics".
            if ($lineItems !== null) {
                $booking->lineItems()->delete();

                foreach ($lineItems as $item) {
                    $booking->lineItems()->create([
                        ...collect($item)->except(['quantity', 'unit_price'])->toArray(),
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'total_value' => round($item['quantity'] * $item['unit_price'], 2),
                    ]);
                }
            }
        });

        $booking->load(['order', 'preparer', 'lineItems']);

        return $this->ok(new BookingResource($booking));
    }

    public function destroy(Booking $booking): JsonResponse
    {
        $booking->delete();

        return $this->noContent();
    }
}

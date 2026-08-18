<?php

namespace Modules\Location\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReceiveStockTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route-level `permission:stock-transfer.receive` middleware is the real gate
    }

    public function rules(): array
    {
        return [
            // May differ from quantity_dispatched — a short/over receipt
            // is recorded as a discrepancy, not rejected (see
            // App\Services\StockTransferService::receive()).
            'quantity_received' => ['required', 'integer', 'min:0'],
        ];
    }
}

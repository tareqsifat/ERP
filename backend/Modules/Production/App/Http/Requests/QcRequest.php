<?php

namespace Modules\Production\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Location\App\Models\Location;

class QcRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route-level `permission:production.qc.record` middleware is the real gate
    }

    public function rules(): array
    {
        return [
            'result' => ['required', Rule::in(['pass', 'reject'])],
            // Required only on reject — checked in withValidator() below.
            'reason' => ['nullable', 'string', 'max:1000'],
            // Required only on pass (PRD v2 §3.18: QC-passed pieces are
            // received into Finished Goods at a Store location) —
            // checked in withValidator() below rather than defaulting to
            // a "Main Store" guessed by name, since nothing in the
            // schema marks one store as canonical.
            'location_id' => ['nullable', 'integer', Rule::exists('locations', 'id')->whereNull('deleted_at')],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->input('result') === 'reject' && ! $this->filled('reason')) {
                $validator->errors()->add('reason', 'A reject reason is required when rejecting a piece.');
            }

            if ($this->input('result') === 'pass') {
                if (! $this->filled('location_id')) {
                    $validator->errors()->add('location_id', 'A Finished Goods intake location is required when passing a piece.');
                } elseif (Location::find($this->input('location_id'))?->type !== 'store') {
                    $validator->errors()->add('location_id', 'QC intake location must be a Store.');
                }
            }
        });
    }
}

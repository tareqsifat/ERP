<?php

namespace Modules\Accounting\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Accounting\App\Models\AccountingCategory;

class StoreVoucherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route-level `permission:accounting.voucher.create` middleware is the real gate
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['credit', 'debit'])],
            'purpose' => ['required', Rule::in(['payment', 'advance', 'general'])],
            'party_id' => ['nullable', 'integer', Rule::exists('parties', 'id')->whereNull('deleted_at')],
            'category_id' => ['nullable', 'integer', Rule::exists('accounting_categories', 'id')->whereNull('deleted_at')],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_type' => ['required', Rule::in(['cash', 'bank', 'cheque'])],
            'bank_account_id' => ['nullable', 'integer', Rule::exists('bank_accounts', 'id')->whereNull('deleted_at')],
            'cheque_id' => ['nullable', 'integer', Rule::exists('cheques', 'id')->whereNull('deleted_at')],
            'date' => ['required', 'date'],
            'bill_no' => ['nullable', 'string', 'max:100'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (in_array($this->input('purpose'), ['payment', 'advance'], true) && ! $this->filled('party_id')) {
                $validator->errors()->add('party_id', 'A party is required for a payment/advance voucher.');
            }

            if ($this->input('payment_type') === 'bank' && ! $this->filled('bank_account_id')) {
                $validator->errors()->add('bank_account_id', 'A bank account is required for a bank payment.');
            }

            if ($this->input('payment_type') === 'cheque' && ! $this->filled('cheque_id')) {
                $validator->errors()->add('cheque_id', 'A cheque is required for a cheque payment.');
            }

            if ($this->filled('category_id')) {
                $category = AccountingCategory::find($this->input('category_id'));
                $expectedKind = $this->input('type') === 'credit' ? 'income' : 'expense';
                if ($category && $category->kind !== $expectedKind) {
                    $validator->errors()->add('category_id', "A {$this->input('type')} voucher must use a {$expectedKind} category.");
                }
            }
        });
    }
}

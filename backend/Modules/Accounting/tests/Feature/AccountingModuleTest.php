<?php

use Modules\Accounting\App\Models\AccountingCategory;
use Modules\Accounting\App\Models\BankAccount;
use Modules\Accounting\App\Services\BankLedgerService;
use Modules\Accounting\App\Services\CashLedgerService;
use Modules\Party\App\Models\Party;

// PRD v1 §3.9/§3.12/§3.13/§4.8/§4.11/§4.12 — Bank, Cash, Cheque, Voucher,
// Party Ledger, Loss & Profit. sdd.md §5: every balance here is a SUM
// over an append-only ledger, never a stored column — these tests assert
// the ledger, not a cached total.

test('depositing and withdrawing from a bank account moves its balance', function () {
    actingAsRole('Accountant');
    $account = BankAccount::factory()->create();

    $this->postJson("/api/v1/bank-accounts/{$account->id}/deposit", ['amount' => 1000])->assertCreated();
    $this->postJson("/api/v1/bank-accounts/{$account->id}/withdraw", ['amount' => 200])->assertCreated();

    $response = $this->getJson("/api/v1/bank-accounts/{$account->id}")->assertOk();
    expect($response->json('data.balance'))->toBe('800.00');
});

test('cash increase/reduce moves the single cash-in-hand balance', function () {
    actingAsRole('Accountant');

    $this->postJson('/api/v1/cash/increase', ['amount' => 500])->assertCreated();
    $this->postJson('/api/v1/cash/reduce', ['amount' => 150])->assertCreated();

    $response = $this->getJson('/api/v1/cash')->assertOk();
    expect($response->json('meta.balance'))->toBe('350.00');
});

test('a cheque only moves the bank ledger once marked passed', function () {
    actingAsRole('Accountant');
    $account = BankAccount::factory()->create();

    $cheque = $this->postJson('/api/v1/cheques', [
        'bank_account_id' => $account->id,
        'cheque_no' => 'CHQ-1001',
        'amount' => 3000,
        'issue_date' => now()->toDateString(),
        'type' => 'expense',
    ])->assertCreated()->json('data');

    expect(BankLedgerService::balanceOf($account->fresh()))->toBe('0.00');

    $this->postJson("/api/v1/cheques/{$cheque['id']}/mark-passed")->assertOk()->assertJsonPath('data.status', 'passed');

    expect(BankLedgerService::balanceOf($account->fresh()))->toBe('-3000.00');

    // Idempotency guard: passing twice would double-post.
    $this->postJson("/api/v1/cheques/{$cheque['id']}/mark-passed")->assertStatus(422);
});

test('a credit cash voucher tied to a party posts to the cash ledger and updates the party due', function () {
    actingAsRole('Accountant');
    $party = Party::factory()->buyer()->create();

    $this->postJson("/api/v1/party-ledger/{$party->id}/bills", [
        'amount' => 5000,
        'bill_date' => now()->toDateString(),
    ])->assertCreated();

    $response = $this->postJson('/api/v1/vouchers', [
        'type' => 'credit',
        'purpose' => 'payment',
        'party_id' => $party->id,
        'amount' => 2000,
        'payment_type' => 'cash',
        'date' => now()->toDateString(),
    ]);

    $response->assertCreated();
    $year = now()->year;
    expect($response->json('data.voucher_no'))->toBe("CR-{$year}-0001");
    expect(CashLedgerService::balance())->toBe('2000.00');

    $ledger = $this->getJson("/api/v1/party-ledger/{$party->id}")->assertOk()->json('data');
    expect($ledger['financials']['total_bill'])->toBe('5000.00');
    expect($ledger['financials']['paid'])->toBe('2000.00');
    expect($ledger['financials']['due'])->toBe('3000.00');
});

test('a voucher category must match the voucher direction (income for credit, expense for debit)', function () {
    actingAsRole('Accountant');
    $incomeCategory = AccountingCategory::factory()->income()->create();

    $this->postJson('/api/v1/vouchers', [
        'type' => 'debit',
        'purpose' => 'general',
        'category_id' => $incomeCategory->id,
        'amount' => 500,
        'payment_type' => 'cash',
        'date' => now()->toDateString(),
    ])->assertStatus(422);
});

test('loss & profit nets credit vouchers against debit vouchers for the year', function () {
    actingAsRole('Accountant');

    $this->postJson('/api/v1/vouchers', [
        'type' => 'credit', 'purpose' => 'general', 'amount' => 5000,
        'payment_type' => 'cash', 'date' => now()->toDateString(),
    ])->assertCreated();
    $this->postJson('/api/v1/vouchers', [
        'type' => 'debit', 'purpose' => 'general', 'amount' => 2000,
        'payment_type' => 'cash', 'date' => now()->toDateString(),
    ])->assertCreated();

    $response = $this->getJson('/api/v1/loss-profit?year='.now()->year)->assertOk();
    expect($response->json('data.total_sale'))->toBe('5000.00');
    expect($response->json('data.total_expense'))->toBe('2000.00');
    expect($response->json('data.total_profit'))->toBe('3000.00');
    expect($response->json('data.total_loss'))->toBe('0.00');
});

test('a user without accounting.voucher.create cannot record a voucher', function () {
    actingAsRole('Showroom Staff');

    $this->postJson('/api/v1/vouchers', [
        'type' => 'credit', 'purpose' => 'general', 'amount' => 100,
        'payment_type' => 'cash', 'date' => now()->toDateString(),
    ])->assertStatus(403);
});

// failed_doc.md §2 Pass 3: a cheque issued for one party could previously
// be attached to a voucher naming a different party_id — a data-
// integrity gap, not an authorization bypass, but worth closing.
test('a voucher cannot attach a cheque that was issued for a different party', function () {
    actingAsRole('Accountant');
    $chequeParty = Party::factory()->supplier()->create();
    $otherParty = Party::factory()->supplier()->create();
    $cheque = \Modules\Accounting\App\Models\Cheque::factory()->create(['party_id' => $chequeParty->id]);

    $this->postJson('/api/v1/vouchers', [
        'type' => 'debit', 'purpose' => 'payment', 'party_id' => $otherParty->id,
        'payment_type' => 'cheque', 'cheque_id' => $cheque->id,
        'amount' => 100, 'date' => now()->toDateString(),
    ])->assertStatus(422)->assertJsonValidationErrors('cheque_id');

    $this->postJson('/api/v1/vouchers', [
        'type' => 'debit', 'purpose' => 'payment', 'party_id' => $chequeParty->id,
        'payment_type' => 'cheque', 'cheque_id' => $cheque->id,
        'amount' => 100, 'date' => now()->toDateString(),
    ])->assertCreated();
});

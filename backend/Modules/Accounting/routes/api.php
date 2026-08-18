<?php

use Illuminate\Support\Facades\Route;
use Modules\Accounting\App\Http\Controllers\AccountingCategoryController;
use Modules\Accounting\App\Http\Controllers\BankAccountController;
use Modules\Accounting\App\Http\Controllers\CashbookController;
use Modules\Accounting\App\Http\Controllers\CashController;
use Modules\Accounting\App\Http\Controllers\ChequeController;
use Modules\Accounting\App\Http\Controllers\LossProfitController;
use Modules\Accounting\App\Http\Controllers\PartyLedgerController;
use Modules\Accounting\App\Http\Controllers\TransactionController;
use Modules\Accounting\App\Http\Controllers\VoucherController;

/*
|--------------------------------------------------------------------------
| Accounting Module API Routes
|--------------------------------------------------------------------------
|
| Included by backend/routes/api.php under the /api/v1 prefix (sdd.md §3).
| PRD v1 §3.9/§3.12/§3.13/§4.8/§4.11/§4.12.
|
*/

Route::middleware('auth.api')->group(function () {
    Route::prefix('accounting-categories')->group(function () {
        Route::get('/', [AccountingCategoryController::class, 'index'])
            ->middleware('permission:accounting.voucher.view')->name('accounting-categories.index');
        Route::post('/', [AccountingCategoryController::class, 'store'])
            ->middleware('permission:accounting.voucher.create')->name('accounting-categories.store');
        Route::put('/{accountingCategory}', [AccountingCategoryController::class, 'update'])
            ->middleware('permission:accounting.voucher.create')->name('accounting-categories.update');
        Route::delete('/{accountingCategory}', [AccountingCategoryController::class, 'destroy'])
            ->middleware('permission:accounting.voucher.create')->name('accounting-categories.destroy');
    });

    Route::prefix('bank-accounts')->middleware('permission:accounting.bank.manage')->group(function () {
        Route::get('/', [BankAccountController::class, 'index'])->name('bank-accounts.index');
        Route::get('/{bankAccount}', [BankAccountController::class, 'show'])->name('bank-accounts.show');
        Route::post('/', [BankAccountController::class, 'store'])->name('bank-accounts.store');
        Route::put('/{bankAccount}', [BankAccountController::class, 'update'])->name('bank-accounts.update');
        Route::delete('/{bankAccount}', [BankAccountController::class, 'destroy'])->name('bank-accounts.destroy');
        Route::get('/{bankAccount}/transactions', [BankAccountController::class, 'transactions'])->name('bank-accounts.transactions');
        Route::post('/{bankAccount}/deposit', [BankAccountController::class, 'deposit'])->name('bank-accounts.deposit');
        Route::post('/{bankAccount}/withdraw', [BankAccountController::class, 'withdraw'])->name('bank-accounts.withdraw');
    });

    Route::prefix('cash')->middleware('permission:accounting.cash.manage')->group(function () {
        Route::get('/', [CashController::class, 'index'])->name('cash.index');
        Route::post('/increase', [CashController::class, 'increase'])->name('cash.increase');
        Route::post('/reduce', [CashController::class, 'reduce'])->name('cash.reduce');
    });

    Route::prefix('cheques')->middleware('permission:accounting.cheque.manage')->group(function () {
        Route::get('/', [ChequeController::class, 'index'])->name('cheques.index');
        Route::post('/', [ChequeController::class, 'store'])->name('cheques.store');
        Route::post('/{cheque}/mark-passed', [ChequeController::class, 'markPassed'])->name('cheques.mark-passed');
    });

    Route::prefix('vouchers')->group(function () {
        Route::get('/', [VoucherController::class, 'index'])
            ->middleware('permission:accounting.voucher.view')->name('vouchers.index');
        Route::get('/{voucher}', [VoucherController::class, 'show'])
            ->middleware('permission:accounting.voucher.view')->name('vouchers.show');
        Route::post('/', [VoucherController::class, 'store'])
            ->middleware('permission:accounting.voucher.create')->name('vouchers.store');
    });

    Route::prefix('party-ledger')->middleware('permission:accounting.ledger.view')->group(function () {
        Route::get('/', [PartyLedgerController::class, 'index'])->name('party-ledger.index');
        Route::get('/{party}', [PartyLedgerController::class, 'show'])->name('party-ledger.show');
        Route::post('/{party}/bills', [PartyLedgerController::class, 'storeBill'])
            ->middleware('permission:accounting.voucher.create')->name('party-ledger.bills.store');
    });

    Route::get('/transactions', [TransactionController::class, 'index'])
        ->middleware('permission:accounting.transaction.view')->name('transactions.index');

    Route::get('/cashbook', [CashbookController::class, 'index'])
        ->middleware('permission:accounting.cashbook.view')->name('cashbook.index');

    Route::get('/loss-profit', [LossProfitController::class, 'index'])
        ->middleware('permission:accounting.loss-profit.view')->name('loss-profit.index');
});

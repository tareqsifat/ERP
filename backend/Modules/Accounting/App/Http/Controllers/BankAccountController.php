<?php

namespace Modules\Accounting\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Accounting\App\Http\Requests\BankTransactionRequest;
use Modules\Accounting\App\Http\Requests\StoreBankAccountRequest;
use Modules\Accounting\App\Http\Requests\UpdateBankAccountRequest;
use Modules\Accounting\App\Http\Resources\BankAccountResource;
use Modules\Accounting\App\Http\Resources\BankTransactionResource;
use Modules\Accounting\App\Models\BankAccount;
use Modules\Accounting\App\Services\BankLedgerService;

// PRD v1 §3.9/§4.8 — Bank Accounts directory + Deposit/Withdraw actions.
class BankAccountController extends Controller
{
    public function index(): JsonResponse
    {
        return $this->ok(BankAccountResource::collection(BankAccount::query()->orderBy('bank_name')->get()));
    }

    public function show(BankAccount $bankAccount): JsonResponse
    {
        return $this->ok(new BankAccountResource($bankAccount));
    }

    public function store(StoreBankAccountRequest $request): JsonResponse
    {
        $account = BankAccount::create($request->validated());

        return $this->created(new BankAccountResource($account));
    }

    public function update(UpdateBankAccountRequest $request, BankAccount $bankAccount): JsonResponse
    {
        $bankAccount->fill($request->validated());
        $bankAccount->save();

        return $this->ok(new BankAccountResource($bankAccount));
    }

    public function destroy(BankAccount $bankAccount): JsonResponse
    {
        $bankAccount->delete();

        return $this->noContent();
    }

    public function transactions(BankAccount $bankAccount): JsonResponse
    {
        return $this->ok(BankTransactionResource::collection($bankAccount->transactions()->orderByDesc('id')->get()));
    }

    public function deposit(BankTransactionRequest $request, BankAccount $bankAccount): JsonResponse
    {
        $transaction = BankLedgerService::deposit($bankAccount, (string) $request->validated('amount'), $request->user()->id, null, $request->validated('remarks'));

        return $this->created(new BankTransactionResource($transaction));
    }

    public function withdraw(BankTransactionRequest $request, BankAccount $bankAccount): JsonResponse
    {
        $transaction = BankLedgerService::withdraw($bankAccount, (string) $request->validated('amount'), $request->user()->id, null, $request->validated('remarks'));

        return $this->created(new BankTransactionResource($transaction));
    }
}

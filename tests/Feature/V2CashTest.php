<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\CashTransaction;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class V2CashTest extends TestCase
{
    use RefreshDatabase;

    public function test_cash_in_creates_transaction_and_journal(): void
    {
        $this->seed();
        $user = User::query()->firstOrFail();
        $company = Company::query()->firstOrFail();

        $cashAcc = Account::query()->where('company_id', $company->id)->whereIn('subtype', ['cash', 'bank'])->firstOrFail();
        $counter = Account::query()->where('company_id', $company->id)->where('is_postable', true)->whereNotIn('subtype', ['cash', 'bank'])->firstOrFail();

        $this->actingAs($user)->get(route('v2.cash.transactions.create'))->assertOk();

        $response = $this->actingAs($user)->post(route('v2.cash.transactions.store'), [
            'type' => 'in',
            'date' => now()->toDateString(),
            'account_id' => $cashAcc->id,
            'counter_account_id' => $counter->id,
            'amount' => 150000,
            'description' => 'Tes kas masuk v2',
        ]);

        $response->assertRedirect(route('v2.cash.transactions'));

        $tx = CashTransaction::query()->where('type', 'in')->latest('id')->first();
        $this->assertNotNull($tx);
        $this->assertEquals(150000, (float) $tx->amount);

        // Jurnal seimbang tercatat untuk transaksi ini.
        $journal = \App\Models\Journal::query()->where('reference', $tx->number)->first();
        $this->assertNotNull($journal, 'Jurnal harus dibuat untuk transaksi kas.');
        $this->assertEquals((float) $journal->total_debit, (float) $journal->total_credit);
    }

    public function test_transfer_rejects_same_account(): void
    {
        $this->seed();
        $user = User::query()->firstOrFail();
        $company = Company::query()->firstOrFail();
        $cashAcc = Account::query()->where('company_id', $company->id)->whereIn('subtype', ['cash', 'bank'])->firstOrFail();

        $response = $this->actingAs($user)->post(route('v2.cash.transactions.store'), [
            'type' => 'transfer',
            'date' => now()->toDateString(),
            'account_id' => $cashAcc->id,
            'to_account_id' => $cashAcc->id,
            'amount' => 5000,
        ]);

        $response->assertSessionHasErrors('to_account_id');
    }
}

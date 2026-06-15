<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\BankReconciliation;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Giro;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class V2GiroReconTest extends TestCase
{
    use RefreshDatabase;

    public function test_pages_render(): void
    {
        $this->seed();
        $user = User::query()->firstOrFail();

        foreach (['v2.cash.giros', 'v2.cash.giros.create', 'v2.cash.reconciliations', 'v2.cash.reconciliations.create'] as $name) {
            $this->actingAs($user)->get(route($name))->assertOk();
        }
    }

    public function test_giro_receive_and_clear(): void
    {
        $this->seed();
        $user = User::query()->firstOrFail();
        $company = Company::query()->firstOrFail();
        $customer = Contact::query()->create(['company_id' => $company->id, 'name' => 'Pelanggan Giro', 'type' => 'customer', 'is_active' => true]);
        $bank = Account::query()->where('company_id', $company->id)->where('subtype', 'bank')->firstOrFail();

        $this->actingAs($user)->post(route('v2.cash.giros.store'), [
            'contact_id' => $customer->id, 'amount' => 500000, 'date' => now()->toDateString(),
            'giro_number' => 'CEK-001', 'bank_name' => 'BCA',
        ])->assertRedirect(route('v2.cash.giros'));

        $giro = Giro::query()->firstOrFail();
        $this->assertEquals('received', $giro->status);

        $this->actingAs($user)->post(route('v2.cash.giros.clear.store', $giro), [
            'bank_account_id' => $bank->id, 'date' => now()->toDateString(),
        ])->assertRedirect(route('v2.cash.giros'));

        $this->assertEquals('cleared', $giro->fresh()->status);
    }

    public function test_bank_reconciliation(): void
    {
        $this->seed();
        $user = User::query()->firstOrFail();
        $company = Company::query()->firstOrFail();
        $bank = Account::query()->where('company_id', $company->id)->where('subtype', 'bank')->firstOrFail();

        $response = $this->actingAs($user)->post(route('v2.cash.reconciliations.store'), [
            'account_id' => $bank->id, 'statement_date' => now()->toDateString(), 'statement_balance' => 1000000,
        ]);

        $rec = BankReconciliation::query()->firstOrFail();
        $response->assertRedirect(route('v2.cash.reconciliations.show', $rec));
        $this->assertEquals(1000000, (float) $rec->statement_balance);
    }
}

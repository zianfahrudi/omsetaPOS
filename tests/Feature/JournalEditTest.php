<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Services\Accounting\ChartOfAccounts;
use App\Services\Accounting\PostingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class JournalEditTest extends TestCase
{
    use RefreshDatabase;

    private function company(): Company
    {
        $company = Company::create(['name' => 'Test Co', 'code' => 'TEST', 'currency' => 'IDR']);
        app(ChartOfAccounts::class)->install($company);

        return $company;
    }

    public function test_manual_journal_can_be_edited_and_stays_balanced(): void
    {
        $company = $this->company();
        $kas = $company->account('cash');
        $bank = $company->account('bank');
        $equity = $company->account('equity');
        $posting = app(PostingService::class);

        $journal = $posting->post(
            company: $company,
            date: now()->toDateString(),
            lines: [
                ['account_id' => $kas->id, 'debit' => 100000],
                ['account_id' => $equity->id, 'credit' => 100000],
            ],
            type: 'general',
            description: 'Setoran awal',
        );
        $number = $journal->number;

        $updated = $posting->update(
            journal: $journal,
            date: now()->toDateString(),
            lines: [
                ['account_id' => $bank->id, 'debit' => 250000],
                ['account_id' => $equity->id, 'credit' => 250000],
            ],
            description: 'Setoran awal (revisi)',
        );

        $this->assertSame($number, $updated->number, 'Nomor jurnal harus tetap.');
        $this->assertEqualsWithDelta(250000, (float) $updated->total_debit, 0.01);
        $this->assertTrue($updated->isBalanced());
        $this->assertCount(2, $updated->lines);
        $this->assertSame('Setoran awal (revisi)', $updated->fresh()->description);
        // Baris lama terganti, bukan tertumpuk.
        $this->assertSame(2, $updated->lines()->count());
    }

    public function test_unbalanced_edit_is_rejected(): void
    {
        $company = $this->company();
        $kas = $company->account('cash');
        $equity = $company->account('equity');
        $posting = app(PostingService::class);

        $journal = $posting->post(
            company: $company,
            date: now()->toDateString(),
            lines: [
                ['account_id' => $kas->id, 'debit' => 100000],
                ['account_id' => $equity->id, 'credit' => 100000],
            ],
            type: 'general',
        );

        $this->expectException(InvalidArgumentException::class);
        $posting->update(
            journal: $journal,
            date: now()->toDateString(),
            lines: [
                ['account_id' => $kas->id, 'debit' => 100000],
                ['account_id' => $equity->id, 'credit' => 80000],
            ],
        );

        // Data lama tetap utuh.
        $this->assertEqualsWithDelta(100000, (float) $journal->fresh()->total_debit, 0.01);
    }

    public function test_auto_posted_journal_cannot_be_edited(): void
    {
        $company = $this->company();
        $kas = $company->account('cash');
        $equity = $company->account('equity');
        $posting = app(PostingService::class);

        // Jurnal bertipe non-general (mensimulasikan jurnal otomatis dari dokumen).
        $journal = $posting->post(
            company: $company,
            date: now()->toDateString(),
            lines: [
                ['account_id' => $kas->id, 'debit' => 50000],
                ['account_id' => $equity->id, 'credit' => 50000],
            ],
            type: 'sales',
        );

        $this->assertFalse($journal->isManual());

        $this->expectException(InvalidArgumentException::class);
        $posting->update(
            journal: $journal,
            date: now()->toDateString(),
            lines: [
                ['account_id' => $kas->id, 'debit' => 60000],
                ['account_id' => $equity->id, 'credit' => 60000],
            ],
        );
    }
}

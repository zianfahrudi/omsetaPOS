<?php

namespace App\Services;

use App\Models\Journal;
use App\Models\ProjectPaymentTerm;
use App\Services\Accounting\PostingService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Posting jurnal saat termin pembayaran proyek dilunasi:
 *   Dr Kas / Bank
 *     Cr Pendapatan Proyek
 * Baris diberi project_id agar masuk laporan laba per proyek.
 */
class ProjectTermPaymentService
{
    public function __construct(private readonly PostingService $posting) {}

    public function markPaid(ProjectPaymentTerm $term, string $method = 'cash', ?int $createdBy = null): void
    {
        if (! in_array($method, ['cash', 'bank'], true)) {
            $method = 'cash';
        }

        $amount = round((float) $term->amount, 2);
        if ($amount <= 0) {
            return; // termin Rp0 → tak ada jurnal
        }

        // Idempoten: jangan posting dua kali untuk termin yang sama.
        if ($this->journalQuery($term)->exists()) {
            return;
        }

        $project = $term->project;
        $company = $project->company;

        $cash = $company->account($method === 'bank' ? 'bank' : 'cash');
        $revenue = $company->account('project_revenue') ?? $company->account('sales');

        if (! $cash || ! $revenue) {
            throw new InvalidArgumentException('Akun Kas/Bank atau Pendapatan Proyek belum dikonfigurasi.');
        }

        $this->posting->post(
            company: $company,
            date: now(),
            lines: [
                ['account_id' => $cash->id, 'debit' => $amount, 'project_id' => $project->id, 'memo' => "Termin {$term->name} - {$project->name}"],
                ['account_id' => $revenue->id, 'credit' => $amount, 'project_id' => $project->id, 'contact_id' => $project->contact_id, 'memo' => "Pendapatan proyek {$project->name}"],
            ],
            type: 'cash_receipt',
            description: "Penerimaan termin {$term->name} - proyek {$project->name}",
            reference: $term->name,
            source: $term,
            createdBy: $createdBy,
        );
    }

    /**
     * Batalkan pelunasan: hapus jurnal yang bersumber dari termin ini.
     */
    public function reverse(ProjectPaymentTerm $term): void
    {
        DB::transaction(function () use ($term) {
            $this->journalQuery($term)->get()->each(function (Journal $journal) {
                $journal->lines()->delete();
                $journal->delete();
            });
        });
    }

    private function journalQuery(ProjectPaymentTerm $term): \Illuminate\Database\Eloquent\Builder
    {
        return Journal::query()
            ->where('source_type', $term->getMorphClass())
            ->where('source_id', $term->getKey());
    }
}

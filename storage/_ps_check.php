<?php
$c = App\Models\Company::first();
$svc = app(App\Services\ProfitDistributionService::class);
$d = $svc->create(
    company: $c,
    from: now()->startOfMonth(),
    to: now()->endOfMonth(),
    baseAmount: 1000000,
    shares: [['name' => 'Owner', 'percent' => 30], ['name' => 'Modal/Gudang', 'percent' => 70]],
    date: now(),
    notes: 'cek',
);
echo 'number='.$d->number."\n";
foreach ($d->shares as $s) {
    echo '  '.$s->name.' '.$s->percent.'% = '.$s->amount."\n";
}
echo 'sumShares='.$d->shares->sum('amount')."\n";
$j = App\Models\Journal::where('source_type', $d->getMorphClass())->where('source_id', $d->id)->with('lines')->first();
echo 'journal='.$j->number.' debit='.$j->total_debit.' credit='.$j->total_credit.' balanced='.(((float)$j->total_debit)===((float)$j->total_credit)?'yes':'no')."\n";
foreach ($j->lines as $l) {
    $acc = App\Models\Account::find($l->account_id);
    echo '  '.$acc->code.' '.$acc->name.' Dr='.$l->debit.' Cr='.$l->credit."\n";
}
// cleanup
$svc->delete($d->fresh());
$gone = App\Models\Journal::where('source_id', $d->id)->where('source_type', $d->getMorphClass())->count();
echo 'afterDelete_journals='.$gone."\n";

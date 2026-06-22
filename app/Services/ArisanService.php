<?php

namespace App\Services;

use App\Models\ArisanGroup;
use App\Models\ArisanMember;
use App\Models\ArisanPayout;
use App\Models\ArisanPeriod;
use App\Support\ActivityLogger;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Arisan karyawan: kelola kelompok, anggota, periode iuran, dan pengundian.
 * Aturan bisnis:
 *  - R1: pemenang tidak boleh menang lagi di kelompok yang sama.
 *  - R4: anggota withdrawn tidak ikut undian.
 *  - R5: dana hanya bisa dicairkan setelah seluruh iuran periode terkumpul.
 */
class ArisanService
{
    public function createGroup(array $data, int $companyId): ArisanGroup
    {
        $data['company_id'] = $companyId;

        return ArisanGroup::create($data);
    }

    public function addMember(ArisanGroup $group, int $employeeId, ?Carbon $joinDate = null): ArisanMember
    {
        $exists = $group->members()->where('employee_id', $employeeId)->exists();
        if ($exists) {
            throw new InvalidArgumentException('Karyawan sudah terdaftar di kelompok arisan ini.');
        }

        $sequence = (int) $group->members()->max('sequence_number') + 1;

        $member = $group->members()->create([
            'employee_id' => $employeeId,
            'join_date' => $joinDate ?? now(),
            'sequence_number' => $sequence,
            'status' => 'active',
            'has_won' => false,
        ]);

        $group->update(['total_members' => $group->members()->count()]);

        return $member;
    }

    /**
     * Buka periode baru: buat period_no berikutnya + iuran pending utk anggota aktif.
     */
    public function openPeriod(ArisanGroup $group, ?Carbon $date = null): ArisanPeriod
    {
        return DB::transaction(function () use ($group, $date) {
            $pending = $group->periods()->where('status', 'pending')->exists();
            if ($pending) {
                throw new InvalidArgumentException('Masih ada periode yang belum selesai. Selesaikan dahulu.');
            }

            $periodNo = (int) $group->periods()->max('period_no') + 1;
            $periodDate = $date ?? now();

            $period = $group->periods()->create([
                'period_no' => $periodNo,
                'period_date' => $periodDate,
                'total_collected' => 0,
                'status' => 'pending',
            ]);

            $members = $group->members()->where('status', 'active')->get();
            foreach ($members as $member) {
                $period->contributions()->create([
                    'employee_id' => $member->employee_id,
                    'amount' => $group->contribution_amount,
                    'contribution_date' => $periodDate,
                    'status' => 'pending',
                ]);
            }

            if ($group->status === 'draft') {
                $group->update(['status' => 'active']);
            }

            ActivityLogger::log('arisan.period_opened', "Periode arisan #{$periodNo} dibuka ({$group->name})", null, $period);

            return $period;
        });
    }

    /**
     * Tandai seluruh iuran periode sebagai terkumpul (dipotong payroll/manual).
     * Opsional integrasi payroll: payrollMap[employee_id] = payroll_id.
     */
    public function collectPeriod(ArisanPeriod $period, array $payrollMap = []): ArisanPeriod
    {
        return DB::transaction(function () use ($period, $payrollMap) {
            $total = 0.0;
            foreach ($period->contributions as $contribution) {
                if ($contribution->status === 'cancelled') {
                    continue;
                }
                $contribution->update([
                    'status' => 'paid',
                    'contribution_date' => $contribution->contribution_date ?? now(),
                    'payroll_id' => $payrollMap[$contribution->employee_id] ?? $contribution->payroll_id,
                ]);
                $total += (float) $contribution->amount;
            }

            $period->update(['total_collected' => round($total, 2)]);

            ActivityLogger::log('arisan.period_collected', "Iuran periode #{$period->period_no} terkumpul Rp".number_format($total, 0, ',', '.'), null, $period);

            return $period->fresh('contributions');
        });
    }

    /**
     * Undi pemenang. Method: random|manual|queue.
     * R1: kandidat = anggota belum menang. R4: status withdrawn dikecualikan.
     * R5: iuran harus terkumpul penuh.
     */
    public function drawWinner(ArisanPeriod $period, string $method = 'random', ?int $manualEmployeeId = null): ArisanPayout
    {
        return DB::transaction(function () use ($period, $method, $manualEmployeeId) {
            if ($period->status === 'completed') {
                throw new InvalidArgumentException('Periode ini sudah memiliki pemenang.');
            }

            if (! $period->allCollected()) {
                throw new InvalidArgumentException('Dana arisan hanya dapat dicairkan setelah seluruh iuran periode terkumpul.');
            }

            $group = $period->group;

            $candidates = $group->members()
                ->where('status', 'active')
                ->where('has_won', false)
                ->orderBy('sequence_number')
                ->get();

            if ($candidates->isEmpty()) {
                throw new InvalidArgumentException('Tidak ada kandidat yang bisa menang (semua anggota sudah pernah menang).');
            }

            $winnerMember = match ($method) {
                'manual' => $candidates->firstWhere('employee_id', $manualEmployeeId)
                    ?? throw new InvalidArgumentException('Karyawan yang dipilih tidak valid sebagai kandidat.'),
                'queue' => $candidates->first(),
                default => $candidates->random(),
            };

            $amount = (float) $period->total_collected;

            $winnerMember->update(['has_won' => true]);

            $period->update([
                'status' => 'completed',
                'winner_employee_id' => $winnerMember->employee_id,
            ]);

            $payout = $period->payout()->create([
                'employee_id' => $winnerMember->employee_id,
                'amount' => $amount,
                'payout_date' => now(),
                'notes' => 'Pencairan dana arisan periode #'.$period->period_no,
            ]);

            // Tandai anggota completed jika semua sudah menang.
            $remaining = $group->members()->where('status', 'active')->where('has_won', false)->count();
            if ($remaining === 0) {
                $group->update(['status' => 'completed']);
            }

            ActivityLogger::log('arisan.winner_drawn', "Pemenang arisan periode #{$period->period_no}: employee #{$winnerMember->employee_id}", null, $payout);

            return $payout;
        });
    }

    /**
     * R2: nonaktifkan anggota yang karyawannya sudah tidak aktif.
     */
    public function syncInactiveMembers(ArisanGroup $group): int
    {
        $count = 0;
        $members = $group->members()->where('status', 'active')->with('employee')->get();
        foreach ($members as $member) {
            if ($member->employee && ! $member->employee->is_active) {
                $member->update(['status' => 'withdrawn']);
                $count++;
            }
        }

        return $count;
    }
}

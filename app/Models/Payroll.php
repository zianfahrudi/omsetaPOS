<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'company_id',
    'employee_id',
    'period_start',
    'period_end',
    'total_hours',
    'gross_salary',
    'total_bonus',
    'total_loan',
    'total_deduction',
    'total_arisan',
    'total_savings',
    'carry_over',
    'take_home_pay',
    'status',
])]
class Payroll extends Model
{
    public const STATUSES = ['draft', 'approved', 'paid'];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'total_hours' => 'decimal:2',
            'gross_salary' => 'decimal:2',
            'total_bonus' => 'decimal:2',
            'total_loan' => 'decimal:2',
            'total_deduction' => 'decimal:2',
            'total_arisan' => 'decimal:2',
            'total_savings' => 'decimal:2',
            'carry_over' => 'decimal:2',
            'take_home_pay' => 'decimal:2',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Normalisasi nomor HP Indonesia ke format internasional (62...).
     */
    public static function normalizePhone(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);
        if ($digits === '' || $digits === null) {
            return null;
        }
        if (str_starts_with($digits, '0')) {
            return '62'.substr($digits, 1);
        }
        if (str_starts_with($digits, '62')) {
            return $digits;
        }
        if (str_starts_with($digits, '8')) {
            return '62'.$digits;
        }

        return $digits;
    }

    /**
     * Teks slip gaji untuk dikirim via WhatsApp.
     */
    public function whatsappText(?string $companyName = null): string
    {
        $rp = fn ($v) => 'Rp '.number_format((float) $v, 0, ',', '.');
        $piece = (bool) $this->employee?->isPiecework();

        $lines = [];
        $lines[] = '*SLIP GAJI*';
        if ($companyName) {
            $lines[] = $companyName;
        }
        $lines[] = 'Periode: '.$this->period_start->format('d/m/Y').' - '.$this->period_end->format('d/m/Y');
        $lines[] = '';
        $lines[] = 'Nama: '.($this->employee?->name ?? '-');
        if (! $piece) {
            $lines[] = 'Jam kerja: '.number_format((float) $this->total_hours, 2).' jam';
        }
        $lines[] = ($piece ? 'Gaji Borongan' : 'Gaji Kotor').': '.$rp($this->gross_salary);
        $lines[] = 'Bonus: +'.$rp($this->total_bonus);
        if ((float) $this->carry_over != 0) {
            $lines[] = 'Sisa Gaji Kemarin: +'.$rp($this->carry_over);
        }
        $lines[] = 'Bon/Kasbon: -'.$rp($this->total_loan);
        $lines[] = 'Potongan: -'.$rp($this->total_deduction);
        $lines[] = 'Arisan: -'.$rp($this->total_arisan);
        $lines[] = 'Tabungan: -'.$rp($this->total_savings);
        $lines[] = '--------------------';
        $lines[] = '*Take Home Pay: '.$rp($this->take_home_pay).'*';

        return implode("\n", $lines);
    }

    /**
     * URL WhatsApp click-to-chat (wa.me). Null bila karyawan tak punya nomor HP.
     */
    public function whatsappUrl(?string $companyName = null): ?string
    {
        $phone = self::normalizePhone($this->employee?->phone);
        if (! $phone) {
            return null;
        }

        return 'https://wa.me/'.$phone.'?text='.rawurlencode($this->whatsappText($companyName));
    }
}

<?php

namespace App\Http\Controllers\V2\Master;

use App\Models\Account;
use App\Models\Tax;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class TaxController extends SimpleCrudController
{
    protected string $modelClass = Tax::class;

    protected string $routeBase = 'v2.taxes';

    protected string $viewForm = 'v2.master.taxes.form';

    protected string $label = 'Pajak';

    protected array $searchColumns = ['name'];

    public const TYPE_LABELS = [
        'ppn' => 'PPN',
        'pph' => 'PPh',
        'other' => 'Lainnya',
    ];

    protected function indexColumns(): array
    {
        return [
            'Nama' => fn (Model $m) => e($m->name),
            'Tipe' => fn (Model $m) => self::TYPE_LABELS[$m->type] ?? e($m->type),
            'Tarif' => fn (Model $m) => number_format((float) $m->rate, 2, ',', '.').'%',
        ];
    }

    protected function rules(Request $request, Model $model): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', 'in:ppn,pph,other'],
            'rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'account_id' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    protected function defaults(): array
    {
        return ['is_active' => true, 'type' => 'ppn', 'rate' => 11];
    }

    protected function formData(): array
    {
        return [
            'typeLabels' => self::TYPE_LABELS,
            'accounts' => Account::query()
                ->where('company_id', $this->companyId())
                ->where('is_postable', true)
                ->orderBy('code')
                ->get(['id', 'code', 'name']),
        ];
    }
}

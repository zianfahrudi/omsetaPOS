<?php

namespace App\Http\Controllers\V2\Master;

use App\Models\Currency;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class CurrencyController extends SimpleCrudController
{
    protected string $modelClass = Currency::class;

    protected string $routeBase = 'v2.currencies';

    protected string $viewForm = 'v2.master.currencies.form';

    protected string $label = 'Mata Uang';

    protected array $searchColumns = ['code', 'name'];

    protected function indexColumns(): array
    {
        return [
            'Kode' => fn (Model $m) => e($m->code).($m->is_default ? ' <span class="ml-1 rounded bg-indigo-50 px-1.5 py-0.5 text-[10px] font-medium text-indigo-600">default</span>' : ''),
            'Nama' => fn (Model $m) => e($m->name),
            'Simbol' => fn (Model $m) => e($m->symbol ?: '—'),
            'Kurs' => fn (Model $m) => number_format((float) $m->exchange_rate, 2, ',', '.'),
        ];
    }

    protected function rules(Request $request, Model $model): array
    {
        return [
            'code' => ['required', 'string', 'max:10'],
            'name' => ['required', 'string', 'max:100'],
            'symbol' => ['nullable', 'string', 'max:10'],
            'exchange_rate' => ['required', 'numeric', 'min:0'],
            'is_default' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    protected function defaults(): array
    {
        return ['is_active' => true, 'exchange_rate' => 1];
    }
}

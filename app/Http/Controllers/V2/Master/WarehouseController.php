<?php

namespace App\Http\Controllers\V2\Master;

use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class WarehouseController extends SimpleCrudController
{
    protected string $modelClass = Warehouse::class;

    protected string $routeBase = 'v2.warehouses';

    protected string $viewForm = 'v2.master.warehouses.form';

    protected string $label = 'Gudang';

    protected function indexColumns(): array
    {
        return [
            'Nama' => fn (Model $m) => e($m->name).($m->is_default ? ' <span class="ml-1 rounded bg-indigo-50 px-1.5 py-0.5 text-[10px] font-medium text-indigo-600">default</span>' : ''),
            'Kode' => fn (Model $m) => e($m->code ?: '—'),
            'Alamat' => fn (Model $m) => e($m->address ?: '—'),
        ];
    }

    protected function rules(Request $request, Model $model): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'code' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:500'],
            'is_default' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}

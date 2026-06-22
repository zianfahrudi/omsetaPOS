<?php

namespace App\Http\Controllers\V2\Master;

use App\Models\Material;
use App\Models\MaterialCategory;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class MaterialController extends SimpleCrudController
{
    protected string $modelClass = Material::class;

    protected string $routeBase = 'v2.materials';

    protected string $viewForm = 'v2.master.materials.form';

    protected string $label = 'Material';

    protected array $searchColumns = ['name', 'category'];

    protected function formData(): array
    {
        return [
            'categories' => MaterialCategory::query()
                ->where('company_id', $this->companyId())
                ->where('is_active', true)
                ->orderBy('name')
                ->pluck('name'),
            'units' => Unit::query()
                ->where('company_id', $this->companyId())
                ->where('is_active', true)
                ->orderBy('name')
                ->pluck('name'),
        ];
    }

    protected function indexColumns(): array
    {
        $rp = fn ($v) => 'Rp '.number_format((float) $v, 0, ',', '.');
        $qty = fn ($v) => rtrim(rtrim(number_format((float) $v, 2, ',', '.'), '0'), ',');

        return [
            'Material' => fn (Model $m) => e($m->name),
            'Kategori' => fn (Model $m) => e($m->category ?: '—'),
            'Satuan' => fn (Model $m) => e($m->unit ?: '—'),
            'Harga' => fn (Model $m) => $rp($m->price),
            'Stok' => fn (Model $m) => $qty($m->stock),
            'Nilai' => fn (Model $m) => $rp($m->stockValue()),
        ];
    }

    protected function rules(Request $request, Model $model): array
    {
        return [
            'category' => ['nullable', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:150'],
            'unit' => ['nullable', 'string', 'max:30'],
            'price' => ['required', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}

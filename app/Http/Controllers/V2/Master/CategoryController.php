<?php

namespace App\Http\Controllers\V2\Master;

use App\Models\Account;
use App\Models\Category;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class CategoryController extends SimpleCrudController
{
    protected string $modelClass = Category::class;

    protected string $routeBase = 'v2.categories';

    protected string $viewForm = 'v2.master.categories.form';

    protected string $label = 'Kategori Produk';

    protected function formData(): array
    {
        return [
            'revenueAccounts' => Account::query()
                ->where('company_id', $this->companyId())
                ->where('type', 'revenue')
                ->where('is_postable', true)
                ->where('is_active', true)
                ->orderBy('code')
                ->get(),
        ];
    }

    protected function indexColumns(): array
    {
        return [
            'Nama' => fn (Model $m) => e($m->name),
            'Kode' => fn (Model $m) => e($m->code ?: '—'),
        ];
    }

    protected function rules(Request $request, Model $model): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'code' => ['nullable', 'string', 'max:30'],
            'revenue_account_id' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}

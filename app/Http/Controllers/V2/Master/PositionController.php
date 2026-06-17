<?php

namespace App\Http\Controllers\V2\Master;

use App\Models\Position;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class PositionController extends SimpleCrudController
{
    protected string $modelClass = Position::class;

    protected string $routeBase = 'v2.positions';

    protected string $viewForm = 'v2.master.simple-form';

    protected string $label = 'Jabatan';

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
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}

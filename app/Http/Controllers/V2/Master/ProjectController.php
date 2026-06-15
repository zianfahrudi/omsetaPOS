<?php

namespace App\Http\Controllers\V2\Master;

use App\Models\Contact;
use App\Models\Project;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class ProjectController extends SimpleCrudController
{
    protected string $modelClass = Project::class;

    protected string $routeBase = 'v2.projects';

    protected string $viewForm = 'v2.master.projects.form';

    protected string $label = 'Proyek';

    protected function indexColumns(): array
    {
        return [
            'Nama' => fn (Model $m) => e($m->name),
            'Kode' => fn (Model $m) => e($m->code ?: '—'),
            'Status' => fn (Model $m) => e(self::STATUS_LABELS[$m->status] ?? $m->status),
            'Anggaran' => fn (Model $m) => 'Rp '.number_format((float) $m->budget, 0, ',', '.'),
        ];
    }

    public const STATUS_LABELS = [
        'planned' => 'Direncanakan',
        'active' => 'Berjalan',
        'completed' => 'Selesai',
        'on_hold' => 'Ditunda',
        'cancelled' => 'Batal',
    ];

    protected function rules(Request $request, Model $model): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'code' => ['nullable', 'string', 'max:30'],
            'contact_id' => ['nullable', 'integer'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['required', 'in:planned,active,completed,on_hold,cancelled'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    protected function defaults(): array
    {
        return ['is_active' => true, 'status' => 'active'];
    }

    protected function formData(): array
    {
        return [
            'statusLabels' => self::STATUS_LABELS,
            'customers' => Contact::query()
                ->where('company_id', $this->companyId())
                ->where('type', 'customer')
                ->orderBy('name')
                ->get(['id', 'name']),
        ];
    }
}

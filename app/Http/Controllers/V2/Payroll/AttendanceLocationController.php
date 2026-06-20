<?php

namespace App\Http\Controllers\V2\Payroll;

use App\Http\Controllers\V2\Master\SimpleCrudController;
use App\Models\AttendanceLocation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttendanceLocationController extends SimpleCrudController
{
    protected string $modelClass = AttendanceLocation::class;

    protected string $routeBase = 'v2.attendance-locations';

    protected string $viewIndex = 'v2.payroll.attendance-locations.index';

    protected string $viewForm = 'v2.payroll.attendance-locations.form';

    protected string $label = 'Titik Lokasi Presensi';

    protected array $searchColumns = ['name', 'address'];

    public function index(Request $request): View
    {
        $view = parent::index($request);

        // Semua titik aktif untuk ditandai di peta (lepas dari pagination/pencarian).
        $points = AttendanceLocation::query()
            ->where('company_id', $this->companyId())
            ->orderBy('name')
            ->get(['id', 'name', 'address', 'latitude', 'longitude', 'radius_meters', 'is_active'])
            ->map(fn (AttendanceLocation $m) => [
                'id' => $m->id,
                'name' => $m->name,
                'address' => $m->address,
                'lat' => (float) $m->latitude,
                'lng' => (float) $m->longitude,
                'radius' => (int) $m->radius_meters,
                'active' => (bool) $m->is_active,
                'edit_url' => route($this->routeBase.'.edit', $m->id),
            ])->values();

        return $view->with('mapPoints', $points);
    }

    protected function indexColumns(): array
    {
        return [
            'Nama' => fn (Model $m) => e($m->name),
            'Alamat' => fn (Model $m) => e($m->address ?: '—'),
            'Koordinat' => fn (Model $m) => e($m->latitude.', '.$m->longitude),
            'Radius' => fn (Model $m) => e($m->radius_meters.' m'),
        ];
    }

    protected function rules(Request $request, Model $model): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'address' => ['nullable', 'string', 'max:500'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'radius_meters' => ['required', 'integer', 'min:10', 'max:5000'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    protected function defaults(): array
    {
        return ['is_active' => true, 'radius_meters' => 100];
    }
}

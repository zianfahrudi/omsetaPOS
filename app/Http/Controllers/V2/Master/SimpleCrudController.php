<?php

namespace App\Http\Controllers\V2\Master;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Base CRUD for simple company-scoped master data (units, warehouses, etc).
 * Subclasses declare the model, route/view names, label, and validation rules.
 */
abstract class SimpleCrudController extends Controller
{
    /** @var class-string<Model> */
    protected string $modelClass;

    protected string $routeBase;   // e.g. 'v2.units'

    protected string $viewIndex = 'v2.master.index';

    protected string $viewForm;    // e.g. 'v2.master.units.form'

    protected string $label;       // e.g. 'Satuan'

    /** Columns searched by the q parameter. */
    protected array $searchColumns = ['name', 'code'];

    /**
     * @return array<string, mixed>
     */
    abstract protected function rules(Request $request, Model $model): array;

    /**
     * Index table columns: ['Header' => callable(Model): string].
     *
     * @return array<string, callable>
     */
    protected function indexColumns(): array
    {
        return ['Nama' => fn (Model $m) => (string) $m->name];
    }

    /**
     * Default attributes for a new record (besides company_id).
     *
     * @return array<string, mixed>
     */
    protected function defaults(): array
    {
        return ['is_active' => true];
    }

    /**
     * Extra data shared with the form view (e.g. select options).
     *
     * @return array<string, mixed>
     */
    protected function formData(): array
    {
        return [];
    }

    public function index(Request $request): View
    {
        $records = $this->modelClass::query()
            ->where('company_id', $this->companyId())
            ->when($request->string('q')->trim()->value(), function ($q, $term) {
                $like = '%'.$term.'%';
                $q->where(function ($w) use ($like) {
                    foreach ($this->searchColumns as $col) {
                        $w->orWhere($col, 'like', $like);
                    }
                });
            })
            ->orderBy($this->searchColumns[0] ?? 'id')
            ->paginate(15)
            ->withQueryString();

        return view($this->viewIndex, [
            'records' => $records,
            'label' => $this->label,
            'routeBase' => $this->routeBase,
            'columns' => $this->indexColumns(),
        ]);
    }

    public function create(): View
    {
        $model = new $this->modelClass($this->defaults());

        return view($this->viewForm, array_merge([
            'record' => $model,
            'label' => $this->label,
            'routeBase' => $this->routeBase,
        ], $this->formData()));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->rules($request, new $this->modelClass));
        $data['company_id'] = $this->companyId();
        $this->modelClass::create($data);

        return redirect()->route($this->routeBase.'.index')->with('status', "{$this->label} berhasil ditambahkan.");
    }

    public function edit(int $id): View
    {
        $model = $this->find($id);

        return view($this->viewForm, array_merge([
            'record' => $model,
            'label' => $this->label,
            'routeBase' => $this->routeBase,
        ], $this->formData()));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $model = $this->find($id);
        $model->update($request->validate($this->rules($request, $model)));

        return redirect()->route($this->routeBase.'.index')->with('status', "{$this->label} berhasil diperbarui.");
    }

    public function destroy(int $id): RedirectResponse
    {
        $this->find($id)->delete();

        return redirect()->route($this->routeBase.'.index')->with('status', "{$this->label} dihapus.");
    }

    protected function find(int $id): Model
    {
        return $this->modelClass::query()
            ->where('company_id', $this->companyId())
            ->findOrFail($id);
    }

    protected function companyId(): ?int
    {
        return Company::query()->value('id');
    }
}

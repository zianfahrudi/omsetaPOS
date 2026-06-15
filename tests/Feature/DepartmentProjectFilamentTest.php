<?php

namespace Tests\Feature;

use App\Filament\Resources\Departments\Pages\ListDepartments;
use App\Filament\Resources\Projects\Pages\CreateProject;
use App\Filament\Resources\Projects\Pages\ListProjects;
use App\Models\Company;
use App\Models\Project;
use App\Models\User;
use App\Services\Accounting\ChartOfAccounts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DepartmentProjectFilamentTest extends TestCase
{
    use RefreshDatabase;

    public function test_department_and_project_pages_work(): void
    {
        $company = Company::create(['name' => 'Test Co', 'code' => 'TEST', 'currency' => 'IDR']);
        app(ChartOfAccounts::class)->install($company);

        $this->actingAs(User::create([
            'name' => 'Admin', 'email' => 'a-dp@test.test',
            'password' => bcrypt('password'), 'role' => 'superuser', 'is_active' => true,
        ]));

        Livewire::test(ListDepartments::class)->assertOk();
        Livewire::test(ListProjects::class)->assertOk();

        Livewire::test(CreateProject::class)
            ->fillForm([
                'company_id' => $company->id,
                'name' => 'Proyek Renovasi',
                'budget' => 5000000,
                'status' => 'active',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('projects', ['company_id' => $company->id, 'name' => 'Proyek Renovasi']);
        $this->assertSame(1, Project::where('company_id', $company->id)->count());
    }
}

<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Project;
use App\Services\Accounting\ChartOfAccounts;
use App\Services\Accounting\PostingService;
use App\Services\Accounting\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DimensionProfitTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_profit_from_tagged_journal_lines(): void
    {
        $company = Company::create(['name' => 'Test Co', 'code' => 'TEST', 'currency' => 'IDR']);
        app(ChartOfAccounts::class)->install($company);

        $project = Project::create([
            'company_id' => $company->id, 'name' => 'Proyek A', 'status' => 'active', 'is_active' => true,
        ]);

        // Revenue 300.000 tagged to project.
        app(PostingService::class)->post(
            company: $company,
            date: now()->toDateString(),
            lines: [
                ['account_id' => $company->account('cash')->id, 'debit' => 300000],
                ['account_id' => $company->account('sales')->id, 'credit' => 300000, 'project_id' => $project->id],
            ],
            type: 'sales',
        );

        // Expense 120.000 tagged to project.
        app(PostingService::class)->post(
            company: $company,
            date: now()->toDateString(),
            lines: [
                ['account_id' => $company->account('operating_expense')->id, 'debit' => 120000, 'project_id' => $project->id],
                ['account_id' => $company->account('cash')->id, 'credit' => 120000],
            ],
            type: 'cash_payment',
        );

        $p = app(ReportService::class)->dimensionProfit($company, 'project_id', $project->id, now()->startOfMonth(), now()->endOfMonth());

        $this->assertSame(300000.0, $p['revenue']);
        $this->assertSame(120000.0, $p['expense']);
        $this->assertSame(180000.0, $p['net']);
    }
}

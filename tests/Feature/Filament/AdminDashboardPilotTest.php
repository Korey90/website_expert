<?php

namespace Tests\Feature\Filament;

use App\Filament\Pages\AdminDashboard;
use App\Filament\Widgets\LeadsBySourceWidget;
use App\Filament\Widgets\ProjectStatusWidget;
use App\Filament\Widgets\QuickActionsWidget;
use App\Filament\Widgets\RecentLeadsWidget;
use App\Filament\Widgets\RevenueChartWidget;
use App\Filament\Widgets\SaasMetricsWidget;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

class AdminDashboardPilotTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'AdminSeeder']);
        Filament::setCurrentPanel('admin');
        $this->actingAs(User::query()->where('email', '20noname22x@gmail.com')->firstOrFail());
    }

    public function test_dashboard_uses_operational_widgets_without_duplicate_quick_actions_or_saas_metrics(): void
    {
        $widgets = app(AdminDashboard::class)->getWidgets();

        $this->assertNotContains(QuickActionsWidget::class, $widgets);
        $this->assertNotContains(SaasMetricsWidget::class, $widgets);
        $this->assertContains(RevenueChartWidget::class, $widgets);
        $this->assertContains(ProjectStatusWidget::class, $widgets);
        $this->assertContains(RecentLeadsWidget::class, $widgets);
    }

    public function test_empty_dashboard_hides_charts_and_tables_without_actionable_data(): void
    {
        $this->assertFalse(RevenueChartWidget::canView());
        $this->assertFalse(LeadsBySourceWidget::canView());
        $this->assertFalse(ProjectStatusWidget::canView());
        $this->assertFalse(RecentLeadsWidget::canView());
    }

    public function test_revenue_chart_always_returns_datasets_as_a_list(): void
    {
        $method = new ReflectionMethod(RevenueChartWidget::class, 'getData');
        $data = $method->invoke(app(RevenueChartWidget::class));

        $this->assertArrayHasKey('datasets', $data);
        $this->assertTrue(array_is_list($data['datasets']));
        $this->assertNotEmpty($data['datasets']);
    }
}

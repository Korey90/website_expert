<?php

namespace App\Filament\Widgets;

use App\Models\Project;
use App\Support\PermissionHelper;
use Filament\Widgets\ChartWidget;

class ProjectStatusWidget extends ChartWidget
{
    public static function canView(): bool
    {
        return PermissionHelper::allows(auth()->user(), 'view_projects')
            && Project::query()->exists();
    }

    protected static ?int $sort = 6;

    protected int|string|array $columnSpan = 1;

    protected string $color = 'primary';

    protected ?string $maxHeight = null;

    public function getHeading(): ?string
    {
        return __('admin.dashboard.widgets.projects_by_status.heading');
    }

    public function getDescription(): ?string
    {
        return __('admin.dashboard.widgets.projects_by_status.description');
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $statuses = ['planning', 'active', 'on_hold', 'completed', 'cancelled'];

        $counts = Project::selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $data = array_map(fn ($s) => (int) ($counts[$s] ?? 0), $statuses);
        $labels = array_map(fn ($s) => ucfirst(str_replace('_', ' ', $s)), $statuses);

        $colors = [
            '#6366f1', // planning  — indigo
            '#22c55e', // active    — green
            '#eab308', // on_hold   — yellow
            '#9ca3af', // completed — gray
            '#ef4444', // cancelled — red
        ];

        return [
            'datasets' => [
                [
                    'label' => 'Projects',
                    'data' => $data,
                    'backgroundColor' => $colors,
                    'borderRadius' => 4,
                    'borderWidth' => 0,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => ['stepSize' => 1],
                ],
            ],
            'plugins' => [
                'legend' => ['display' => false],
            ],
        ];
    }
}

<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use App\Filament\Widgets\DashboardDateFilter;
use App\Filament\Widgets\OverviewStatsWidget;
use App\Filament\Widgets\SecondaryStatsWidget;
use App\Filament\Widgets\TodayTimeline;

class Dashboard extends BaseDashboard
{
    public function getWidgets(): array
    {
        return [
            DashboardDateFilter::class,
            OverviewStatsWidget::class,
            SecondaryStatsWidget::class,
            TodayTimeline::class,
        ];
    }
}

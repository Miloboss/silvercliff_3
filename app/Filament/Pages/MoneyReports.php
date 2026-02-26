<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\Booking;
use App\Traits\HasDashboardDateFilter;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Filament\Widgets\RevenueOverviewWidget;
use App\Filament\Widgets\DashboardDateFilter;

class MoneyReports extends Page
{
    use HasDashboardDateFilter;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static string $view = 'filament.pages.money-reports';

    protected static ?string $navigationLabel = 'Money';

    protected static ?string $navigationGroup = 'Financials';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Financial Reports';

    protected static ?string $slug = 'money';

    protected function getHeaderWidgets(): array
    {
        return [
            DashboardDateFilter::class,
            RevenueOverviewWidget::class,
        ];
    }

    public function getColumns(): int | string | array
    {
        return 1;
    }
}

<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Session;

class DashboardDateFilter extends Widget
{
    protected static string $view = 'filament.widgets.dashboard-date-filter';
    protected static ?int $sort = -1;
    protected int | string | array $columnSpan = 'full';

    public $from;
    public $until;

    public function mount()
    {
        $this->from = Session::get('dashboard_date_from', now()->toDateString());
        $this->until = Session::get('dashboard_date_until', now()->toDateString());
    }

    public function updated($property)
    {
        Session::put('dashboard_date_from', $this->from);
        Session::put('dashboard_date_until', $this->until);
        $this->dispatch('dashboard-date-updated', from: $this->from, until: $this->until);
    }

    public function setToday()
    {
        $this->from = now()->toDateString();
        $this->until = now()->toDateString();
        $this->updated('from');
    }

    public function setTomorrow()
    {
        $this->from = now()->addDay()->toDateString();
        $this->until = now()->addDay()->toDateString();
        $this->updated('from');
    }

    public function setThisWeek()
    {
        $this->from = now()->startOfWeek()->toDateString();
        $this->until = now()->endOfWeek()->toDateString();
        $this->updated('from');
    }

    public function setThisMonth()
    {
        $this->from = now()->startOfMonth()->toDateString();
        $this->until = now()->endOfMonth()->toDateString();
        $this->updated('from');
    }

    public function setLastMonth()
    {
        $this->from = now()->subMonth()->startOfMonth()->toDateString();
        $this->until = now()->subMonth()->endOfMonth()->toDateString();
        $this->updated('from');
    }

    public function setThisYear()
    {
        $this->from = now()->startOfYear()->toDateString();
        $this->until = now()->endOfYear()->toDateString();
        $this->updated('from');
    }
}

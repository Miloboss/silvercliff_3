<?php

namespace App\Traits;

use Illuminate\Support\Facades\Session;

trait HasDashboardDateFilter
{
    public $fromDate;
    public $untilDate;

    protected function getListeners()
    {
        return array_merge(parent::getListeners(), [
            'dashboard-date-updated' => 'updateDates',
        ]);
    }

    public function mountHasDashboardDateFilter()
    {
        $this->fromDate = Session::get('dashboard_date_from', now()->toDateString());
        $this->untilDate = Session::get('dashboard_date_until', now()->toDateString());
    }

    public function updateDates($from, $until)
    {
        $this->fromDate = $from;
        $this->untilDate = $until;
        
        if (method_exists($this, 'resetTable')) {
            $this->resetTable();
        }
    }

    protected function getDateRange(): array
    {
        return [
            $this->fromDate ?? Session::get('dashboard_date_from', now()->toDateString()),
            $this->untilDate ?? Session::get('dashboard_date_until', now()->toDateString())
        ];
    }
}

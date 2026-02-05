<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use App\Models\BookingRoomDetail;
use App\Models\BookingTourDetail;
use App\Models\BookingPackageDetail;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Traits\HasDashboardDateFilter;

class KPIStatsWidget extends BaseWidget
{
    use HasDashboardDateFilter;

    protected static ?int $sort = 0;

    protected function getStats(): array
    {
        [$from, $until] = $this->getDateRange();

        // 1. Core Status Counts (Global for range)
        $statusQuery = Booking::where(function ($q) use ($from, $until) {
            $q->whereHas('roomDetail', fn($sq) => $sq->where('check_in', '<=', $until)->where('check_out', '>', $from))
              ->orWhereHas('packageDetail', fn($sq) => $sq->where('check_in', '<=', $until)->where('check_out', '>', $from))
              ->orWhereHas('tourDetail', fn($sq) => $sq->whereBetween('tour_date', [$from, $until]));
        });

        // 2. Operational Metrics (Confirmed Only)
        $checkIns = BookingRoomDetail::whereBetween('check_in', [$from, $until])
            ->whereHas('booking', fn($q) => $q->where('status', 'confirmed'))
            ->count() +
            BookingPackageDetail::whereBetween('check_in', [$from, $until])
            ->whereHas('booking', fn($q) => $q->where('status', 'confirmed'))
            ->count();

        $checkOuts = BookingRoomDetail::whereBetween('check_out', [$from, $until])
            ->whereHas('booking', fn($q) => $q->where('status', 'confirmed'))
            ->count() +
            BookingPackageDetail::whereBetween('check_out', [$from, $until])
            ->whereHas('booking', fn($q) => $q->where('status', 'confirmed'))
            ->count();

        $tourGuests = BookingTourDetail::whereBetween('tour_date', [$from, $until])
            ->whereHas('booking', fn($q) => $q->where('status', 'confirmed'))
            ->get();
        
        $pkgGuestsCount = BookingPackageDetail::where('check_in', '<=', $until)
            ->where('check_out', '>', $from)
            ->whereHas('booking', fn($q) => $q->where('status', 'confirmed'))
            ->get()
            ->sum(fn($d) => $d->guests_adults + $d->guests_children);

        return [
            Stat::make('Pending', (clone $statusQuery)->where('status', 'pending')->count())->color('gray'),
            Stat::make('Confirmed', (clone $statusQuery)->where('status', 'confirmed')->count())->color('success'),
            Stat::make('Check-ins', $checkIns)->color('success'),
            Stat::make('Check-outs', $checkOuts)->color('warning'),
            Stat::make('Tour Guests', $tourGuests->sum(fn($d) => $d->guests_adults + $d->guests_children))->color('primary'),
            Stat::make('Package Guests', $pkgGuestsCount)->color('info'),
        ];
    }
}

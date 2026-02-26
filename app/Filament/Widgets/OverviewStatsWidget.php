<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use App\Models\BookingRoomDetail;
use App\Models\BookingTourDetail;
use App\Models\BookingPackageDetail;
use App\Models\BookingRoomAssignment;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Traits\HasDashboardDateFilter;
use Carbon\Carbon;

class OverviewStatsWidget extends BaseWidget
{
    use HasDashboardDateFilter;

    protected static ?int $sort = 1;

    protected function getColumns(): int
    {
        return 4;
    }

    protected function getStats(): array
    {
        [$from, $until] = $this->getDateRange();
        $isToday = ($from === $until && $from === Carbon::today()->toDateString());

        // 1. Today New Arrivals (Confirmed room/package bookings checking in)
        $arrivalsCount = Booking::whereIn('status', ['confirmed'])
            ->where(function ($query) use ($from, $until) {
                $query->whereHas('roomDetail', fn($q) => $q->whereBetween('check_in', [$from, $until]))
                    ->orWhereHas('packageDetail', fn($q) => $q->whereBetween('check_in', [$from, $until]));
            })
            ->count();

        // 2. Guests Staying (Confirmed bookings active today)
        // today >= check_in AND today < check_out AND status = confirmed
        $stayingCount = Booking::where('status', 'confirmed')
            ->where(function ($query) use ($from, $until) {
                $query->whereHas('roomDetail', fn($q) => $q->where('check_in', '<=', $until)->where('check_out', '>', $from))
                    ->orWhereHas('packageDetail', fn($q) => $q->where('check_in', '<=', $until)->where('check_out', '>', $from));
            })
            ->count();

        // 3. Today Check-outs / Departures
        $departuresCount = Booking::whereIn('status', ['confirmed'])
            ->where(function ($query) use ($from, $until) {
                $query->whereHas('roomDetail', fn($q) => $q->whereBetween('check_out', [$from, $until]))
                    ->orWhereHas('packageDetail', fn($q) => $q->whereBetween('check_out', [$from, $until]));
            })
            ->count();

        // 4. Pending Bookings (All time pending bookings)
        $pendingCount = Booking::where('status', 'pending')->count();

        // 5. Today Tours (Tours scheduled for today)
        $toursCount = Booking::where('booking_type', 'tour')
            ->whereIn('status', ['confirmed'])
            ->whereHas('tourDetail', fn($q) => $q->whereBetween('tour_date', [$from, $until]))
            ->count();

        // 6. Today Packages (Packages active today)
        $packagesCount = Booking::where('booking_type', 'package')
            ->whereIn('status', ['confirmed'])
            ->whereHas('packageDetail', fn($q) => $q->where('check_in', '<=', $until)->where('check_out', '>', $from))
            ->count();

        $dateParams = "?tableFilters[date_range][from]={$from}&tableFilters[date_range][until]={$until}";

        return [
            Stat::make('New Arrivals', $arrivalsCount)
                ->description($isToday ? 'Today\'s check-ins' : 'Arrivals in range')
                ->color('success')
                ->url('/admin/bookings' . $dateParams . '&tableFilters[status][value]=confirmed'),

            Stat::make('Guests Staying', $stayingCount)
                ->description('Currently checked-in')
                ->color('info')
                ->url('/admin/bookings?tableFilters[staying][value]=1'),

            Stat::make('Check-outs', $departuresCount)
                ->description($isToday ? 'Today\'s departures' : 'Departures in range')
                ->color('warning')
                ->url('/admin/bookings' . $dateParams . '&tableFilters[status][value]=confirmed'),

            Stat::make('Pending Bookings', $pendingCount)
                ->description('Needs attention')
                ->color('danger')
                ->url('/admin/bookings?tableFilters[status][value]=pending'),

            Stat::make('Today Tours', $toursCount)
                ->description($isToday ? 'Scheduled for today' : 'Tours in range')
                ->color('primary')
                ->url('/admin/bookings' . $dateParams . '&tableFilters[booking_type][value]=tour&tableFilters[status][value]=confirmed'),

            Stat::make('Today Packages', $packagesCount)
                ->description($isToday ? 'Active packages' : 'Packages in range')
                ->color('success')
                ->url('/admin/bookings' . $dateParams . '&tableFilters[booking_type][value]=package&tableFilters[status][value]=confirmed'),
        ];
    }
}

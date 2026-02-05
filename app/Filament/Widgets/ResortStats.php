<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use App\Models\BookingRoomDetail;
use App\Models\BookingTourDetail;
use App\Models\BookingPackageDetail;
use App\Models\BookingRoomAssignment;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;

class ResortStats extends BaseWidget
{
    protected function getStats(): array
    {
        $today = Carbon::today()->toDateString();

        // 1. Check-ins today
        $checkInsSub = BookingRoomDetail::where('check_in', $today)->count();
        $pkgCheckInsSub = BookingPackageDetail::where('check_in', $today)->count();
        $totalCheckIns = $checkInsSub + $pkgCheckInsSub;

        // 2. Check-outs today
        $checkOutsSub = BookingRoomDetail::where('check_out', $today)->count();
        $pkgCheckOutsSub = BookingPackageDetail::where('check_out', $today)->count();
        $totalCheckOuts = $checkOutsSub + $pkgCheckOutsSub;

        // 3. Guests staying now (Room assignments active today)
        $stayingNow = BookingRoomAssignment::where('assigned_from', '<=', $today)
            ->where('assigned_to', '>=', $today)
            ->count();

        // 4. Today Tour guests
        $tourGuests = BookingTourDetail::where('tour_date', $today)->sum('guests_adults') + 
                      BookingTourDetail::where('tour_date', $today)->sum('guests_children');

        // 5. Today Package guests
        // Count guests for packages that are active today
        $pkgGuests = BookingPackageDetail::where('check_in', '<=', $today)
            ->where('check_out', '>=', $today)
            ->sum('guests_adults') +
            BookingPackageDetail::where('check_in', '<=', $today)
            ->where('check_out', '>=', $today)
            ->sum('guests_children');

        return [
            Stat::make('Today Check-ins', $totalCheckIns)
                ->description('New arrivals')
                ->color('success'),
            Stat::make('Today Check-outs', $totalCheckOuts)
                ->description('Departures')
                ->color('warning'),
            Stat::make('Guests Staying', $stayingNow)
                ->description('Occupied rooms')
                ->color('info'),
            Stat::make('Today Tours', $tourGuests)
                ->description('Total adventurers')
                ->color('primary'),
            Stat::make('Today Packages', $pkgGuests)
                ->description('Packaged stay guests')
                ->color('success'),
        ];
    }
}

<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use App\Traits\HasDashboardDateFilter;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class RevenueOverviewWidget extends BaseWidget
{
    use HasDashboardDateFilter;

    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        [$from, $until] = $this->getDateRange();
        $isToday = ($from === $until && $from === Carbon::today()->toDateString());

        // Helper to query bookings active in range
        $getFiltered = fn($type = null) => Booking::whereIn('status', ['pending', 'confirmed'])
            ->when($type, fn($q) => $q->where('booking_type', $type))
            ->where(function ($query) use ($from, $until) {
                $query->whereHas('roomDetail', fn($q) => $q->where('check_in', '<=', $until)->where('check_out', '>', $from))
                    ->orWhereHas('packageDetail', fn($q) => $q->where('check_in', '<=', $until)->where('check_out', '>', $from))
                    ->orWhereHas('tourDetail', fn($q) => $q->whereBetween('tour_date', [$from, $until]));
            });

        $totalRevenue = $getFiltered()->sum('total_amount');
        
        // Detailed stats for Rooms
        $roomBookings = $getFiltered('room')->with('roomDetail')->get();
        $roomRev = $roomBookings->sum('total_amount');
        $roomGuests = $roomBookings->sum(fn($b) => ($b->roomDetail->guests_adults ?? 0) + ($b->roomDetail->guests_children ?? 0));
        $occupiedRooms = $roomBookings->count(); // In our current simplified model, 1 booking = 1 room detail

        // Detailed stats for Tours
        $tourBookings = $getFiltered('tour')->with('tourDetail')->get();
        $tourRev = $tourBookings->sum('total_amount');
        $tourGuests = $tourBookings->sum(fn($b) => ($b->tourDetail->guests_adults ?? 0) + ($b->tourDetail->guests_children ?? 0));

        // Detailed stats for Packages
        $pkgBookings = $getFiltered('package')->with('packageDetail')->get();
        $pkgRev = $pkgBookings->sum('total_amount');
        $pkgGuests = $pkgBookings->sum(fn($b) => ($b->packageDetail->guests_adults ?? 0) + ($b->packageDetail->guests_children ?? 0));

        $totalGuests = $roomGuests + $tourGuests + $pkgGuests;

        return [
            Stat::make('Total Revenue', number_format($totalRevenue) . ' THB')
                ->description('Total expected in range')
                ->color('success')
                ->icon('heroicon-o-currency-dollar'),

            Stat::make('Total Guests', $totalGuests)
                ->description('People total')
                ->color('info')
                ->icon('heroicon-o-users'),

            Stat::make('Room Metrics', number_format($roomRev) . ' THB')
                ->description("$occupiedRooms bookings | $roomGuests guests")
                ->color('primary'),

            Stat::make('Tour Metrics', number_format($tourRev) . ' THB')
                ->description("$tourGuests guests total")
                ->color('warning'),

            Stat::make('Package Metrics', number_format($pkgRev) . ' THB')
                ->description("$pkgGuests guests total")
                ->color('success'),
        ];
    }
}

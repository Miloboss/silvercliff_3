<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use App\Models\BookingTourDetail;
use App\Models\BookingPackageDetail;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SecondaryStatsWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected function getColumns(): int
    {
        return 3;
    }

    protected function getHeading(): ?string
    {
        return 'Detailed Statistics';
    }

    protected function getStats(): array
    {
        // 1. Total Adventurers (Sum of all guests in confirmed bookings)
        $totalGuests = Booking::whereIn('status', ['confirmed'])
            ->with(['roomDetail', 'tourDetail', 'packageDetail'])
            ->get()
            ->sum(function ($booking) {
                $detail = $booking->roomDetail ?? $booking->tourDetail ?? $booking->packageDetail;
                return $detail ? ($detail->guests_adults + $detail->guests_children) : 0;
            });

        // 2. Package guests (all-time)
        $packageGuests = BookingPackageDetail::whereHas('booking', fn($q) => $q->where('status', 'confirmed'))
            ->get()
            ->sum(fn($d) => $d->guests_adults + $d->guests_children);

        // 3. Tour guests (all-time)
        $tourGuests = BookingTourDetail::whereHas('booking', fn($q) => $q->where('status', 'confirmed'))
            ->get()
            ->sum(fn($d) => $d->guests_adults + $d->guests_children);

        return [
            Stat::make('Total Adventurers', $totalGuests)
                ->description('All-time guests')
                ->color('gray'),

            Stat::make('Package Guests', $packageGuests)
                ->description('All-time package stays')
                ->color('gray'),

            Stat::make('Tour Guests', $tourGuests)
                ->description('All-time tour participants')
                ->color('gray'),
        ];
    }
}

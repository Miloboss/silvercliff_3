<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use App\Models\BookingRoomDetail;
use App\Models\BookingPackageDetail;
use App\Models\BookingScheduleItem;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Traits\HasDashboardDateFilter;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TodayTimeline extends BaseWidget
{
    use HasDashboardDateFilter;

    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        [$from, $until] = $this->getDateRange();
        $isToday = ($from === $until && $from === Carbon::today()->toDateString());

        $tours = DB::table('booking_schedule_items')
            ->join('bookings', 'booking_schedule_items.booking_id', '=', 'bookings.id')
            ->whereBetween('scheduled_date', [$from, $until])
            ->select([
                DB::raw("CONCAT('activity_', booking_schedule_items.id) as id"),
                'bookings.id as booking_id',
                'bookings.full_name as guest_name',
                'bookings.booking_type as type',
                'booking_schedule_items.title as event_title',
                'booking_schedule_items.scheduled_time as event_time',
                DB::raw("'activity' as category")
            ]);

        $checkins = DB::table('booking_room_details')
            ->join('bookings', 'booking_room_details.booking_id', '=', 'bookings.id')
            ->whereBetween('check_in', [$from, $until])
            ->select([
                DB::raw("CONCAT('arrival_r_', booking_room_details.id) as id"),
                'bookings.id as booking_id',
                'bookings.full_name as guest_name',
                'bookings.booking_type as type',
                DB::raw("'Check-in (Room Only)' as event_title"),
                DB::raw("'14:00:00' as event_time"),
                DB::raw("'arrival' as category")
            ]);

        $pkgCheckins = DB::table('booking_package_details')
            ->join('bookings', 'booking_package_details.booking_id', '=', 'bookings.id')
            ->whereBetween('check_in', [$from, $until])
            ->select([
                DB::raw("CONCAT('arrival_p_', booking_package_details.id) as id"),
                'bookings.id as booking_id',
                'bookings.full_name as guest_name',
                'bookings.booking_type as type',
                DB::raw("'Check-in (Package)' as event_title"),
                DB::raw("'14:00:00' as event_time"),
                DB::raw("'arrival' as category")
            ]);

        $checkouts = DB::table('booking_room_details')
            ->join('bookings', 'booking_room_details.booking_id', '=', 'bookings.id')
            ->whereBetween('check_out', [$from, $until])
            ->select([
                DB::raw("CONCAT('departure_r_', booking_room_details.id) as id"),
                'bookings.id as booking_id',
                'bookings.full_name as guest_name',
                'bookings.booking_type as type',
                DB::raw("'Check-out' as event_title"),
                DB::raw("'11:00:00' as event_time"),
                DB::raw("'departure' as category")
            ]);

        $pkgCheckouts = DB::table('booking_package_details')
            ->join('bookings', 'booking_package_details.booking_id', '=', 'bookings.id')
            ->whereBetween('check_out', [$from, $until])
            ->select([
                DB::raw("CONCAT('departure_p_', booking_package_details.id) as id"),
                'bookings.id as booking_id',
                'bookings.full_name as guest_name',
                'bookings.booking_type as type',
                DB::raw("'Check-out (Package)' as event_title"),
                DB::raw("'11:00:00' as event_time"),
                DB::raw("'departure' as category")
            ]);

        $unionQuery = $tours->union($checkins)
            ->union($pkgCheckins)
            ->union($checkouts)
            ->union($pkgCheckouts);

        return $table
            ->heading($isToday ? 'Today\'s Operational Timeline' : 'Operational Timeline for ' . $from . ' - ' . $until)
            ->query(
                \App\Models\BookingScheduleItem::query()
                    ->fromSub($unionQuery, 'operational_timeline')
                    ->orderBy('event_time', 'asc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('event_time')
                    ->label('Time')
                    ->formatStateUsing(fn ($state) => Carbon::parse($state)->format('H:i'))
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('event_title')
                    ->label('Event')
                    ->description(fn ($record) => $record->guest_name),

                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'room' => 'info',
                        'tour' => 'warning',
                        'package' => 'success',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('room_code')
                    ->label('Room')
                    ->getStateUsing(function ($record) {
                        return DB::table('booking_room_assignments')
                            ->join('rooms', 'booking_room_assignments.room_id', '=', 'rooms.id')
                            ->where('booking_id', $record->booking_id)
                            ->value('room_code') ?? 'N/A';
                    })
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('category')
                    ->label('Category')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'arrival' => 'success',
                        'departure' => 'danger',
                        'activity' => 'primary',
                        default => 'gray',
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->url(fn ($record) => "/admin/bookings/{$record->booking_id}/edit")
                    ->icon('heroicon-o-eye')
            ]);
    }
}

<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Traits\HasDashboardDateFilter;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class RoomStayTimelineWidget extends BaseWidget
{
    use HasDashboardDateFilter;

    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 'full';
    protected static ?string $heading = 'Room Timeline (Confirmed Only)';

    public function table(Table $table): Table
    {
        [$from, $until] = $this->getDateRange();

        $query = Booking::query()
            ->where('booking_type', 'room')
            ->where('status', 'confirmed')
            ->whereHas('roomDetail', fn($q) => $q->where('check_in', '<=', $until)->where('check_out', '>=', $from))
            ->with(['roomDetail', 'roomAssignments.room']);

        if ($query->count() === 0) {
            Log::info("RoomStayTimelineWidget: 0 results for range $from to $until.");
        }

        return $table
            ->query($query)
            ->columns([
                Tables\Columns\TextColumn::make('stage')
                    ->badge()
                    ->getStateUsing(function (Booking $record) use ($from, $until) {
                        $details = $record->roomDetail;
                        if (!$details) return 'Unknown';
                        
                        // Check-in today
                        if ($details->check_in >= $from && $details->check_in <= $until) return 'Arrival';
                        
                        // Check-out today
                        if ($details->check_out >= $from && $details->check_out <= $until) return 'Departure';
                        
                        return 'Staying';
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'Arrival' => 'success',
                        'Departure' => 'warning',
                        'Staying' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('full_name')->searchable(),
                Tables\Columns\TextColumn::make('roomAssignments.room.room_code')->label('Room')->badge(),
                Tables\Columns\TextColumn::make('roomDetail.check_in')->label('In')->date(),
                Tables\Columns\TextColumn::make('roomDetail.check_out')->label('Out')->date(),
                Tables\Columns\TextColumn::make('guests')
                    ->getStateUsing(fn($record) => "{$record->roomDetail?->guests_adults} A, {$record->roomDetail?->guests_children} C"),
            ]);
    }
}

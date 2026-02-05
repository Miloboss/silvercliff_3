<?php

namespace App\Filament\Widgets;

use App\Models\BookingScheduleItem;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Traits\HasDashboardDateFilter;
use Illuminate\Support\Facades\Log;

class PackageTimelineWidget extends BaseWidget
{
    use HasDashboardDateFilter;

    protected static ?int $sort = 4;
    protected int | string | array $columnSpan = 'full';
    protected static ?string $heading = 'Package Itineraries (Confirmed Only)';

    public function table(Table $table): Table
    {
        [$from, $until] = $this->getDateRange();

        $query = BookingScheduleItem::query()
            ->whereHas('booking', fn($q) => $q->where('booking_type', 'package')->where('status', 'confirmed'))
            ->whereBetween('scheduled_date', [$from, $until])
            ->with(['booking.packageDetail'])
            ->orderBy('scheduled_time');

        // Debugging output per user request
        if ($query->count() === 0) {
            Log::info("PackageTimelineWidget: Query returned 0 results for range $from to $until. SQL: " . $query->toSql());
        }

        return $table
            ->query($query)
            ->columns([
                Tables\Columns\TextColumn::make('scheduled_time')->label('Time')->time(),
                Tables\Columns\TextColumn::make('title')->label('Activity'),
                Tables\Columns\TextColumn::make('booking.full_name')->label('Guest'),
                Tables\Columns\TextColumn::make('booking.booking_code')
                    ->label('Code')
                    ->badge()
                    ->color('success')
                    ->name('booking_code_col'), // Naming it for grouping
                Tables\Columns\TextColumn::make('room')
                    ->label('Stay Range')
                    ->getStateUsing(fn($record) => "{$record->booking->packageDetail?->check_in} - {$record->booking->packageDetail?->check_out}"),
            ])
            ->defaultGroup('booking.booking_code');
    }
}

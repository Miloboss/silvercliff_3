<?php

namespace App\Filament\Widgets;

use App\Models\BookingTourDetail;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Traits\HasDashboardDateFilter;
use Illuminate\Support\Facades\Log;

class TourTimelineWidget extends BaseWidget
{
    use HasDashboardDateFilter;

    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 'full';
    protected static ?string $heading = 'Tour Operations (Confirmed Only)';

    public function table(Table $table): Table
    {
        [$from, $until] = $this->getDateRange();

        $query = BookingTourDetail::query()
            ->whereHas('booking', fn($q) => $q->where('status', 'confirmed'))
            ->whereBetween('tour_date', [$from, $until])
            ->with(['booking', 'activity'])
            ->orderBy('tour_time');

        if ($query->count() === 0) {
            Log::info("TourTimelineWidget: 0 results for range $from to $until. SQL: " . $query->toSql());
        }

        return $table
            ->query($query)
            ->columns([
                Tables\Columns\TextColumn::make('tour_time')->label('Time')->time(),
                Tables\Columns\TextColumn::make('activity.title')->label('Tour'),
                Tables\Columns\TextColumn::make('booking.full_name')->label('Guest'),
                Tables\Columns\TextColumn::make('guests')
                    ->getStateUsing(fn ($record) => "{$record->guests_adults} A, {$record->guests_children} C"),
                Tables\Columns\TextColumn::make('booking.booking_code')->label('Code')->badge(),
            ]);
    }
}

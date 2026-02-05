<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Traits\HasDashboardDateFilter;
use Illuminate\Support\Facades\Log;

class TodayPendingWidget extends BaseWidget
{
    use HasDashboardDateFilter;

    protected static ?int $sort = 1;
    protected int | string | array $columnSpan = 'full';
    protected static ?string $heading = 'Action Required: Pending Bookings for Selected Range';

    public function table(Table $table): Table
    {
        [$from, $until] = $this->getDateRange();

        $query = Booking::query()
            ->where('status', 'pending')
            ->where(function ($q) use ($from, $until) {
                // Using User Rule #3 logic for selection
                $q->whereHas('roomDetail', fn($sq) => $sq->where('check_in', '<=', $until)->where('check_out', '>', $from))
                  ->orWhereHas('packageDetail', fn($sq) => $sq->where('check_in', '<=', $until)->where('check_out', '>', $from))
                  ->orWhereHas('tourDetail', fn($sq) => $sq->whereBetween('tour_date', [$from, $until]));
            });

        if ($query->count() === 0) {
            Log::info("TodayPendingWidget: 0 results for range $from to $until.");
        }

        return $table
            ->query($query)
            ->columns([
                Tables\Columns\TextColumn::make('booking_code')->weight('bold'),
                Tables\Columns\TextColumn::make('booking_type')->badge(),
                Tables\Columns\TextColumn::make('details')
                    ->getStateUsing(function (Booking $record): string {
                        if ($record->booking_type === 'tour') return "Tour on " . $record->tourDetail?->tour_date;
                        $d = $record->booking_type === 'room' ? $record->roomDetail : $record->packageDetail;
                        return "Stay: " . ($d?->check_in ?? '') . " -> " . ($d?->check_out ?? '');
                    }),
                Tables\Columns\TextColumn::make('full_name'),
                Tables\Columns\TextColumn::make('whatsapp'),
            ])
            ->actions([
                Tables\Actions\Action::make('confirm')
                    ->color('success')
                    ->icon('heroicon-o-check')
                    ->action(function (Booking $record) {
                        $record->update(['status' => 'confirmed']);
                        $this->dispatch('dashboard-date-updated', from: $this->fromDate, until: $this->untilDate);
                    }),
                Tables\Actions\Action::make('cancel')
                    ->color('danger')
                    ->icon('heroicon-o-x-mark')
                    ->requiresConfirmation()
                    ->action(function (Booking $record) {
                        $record->update(['status' => 'cancelled']);
                        $this->dispatch('dashboard-date-updated', from: $this->fromDate, until: $this->untilDate);
                    }),
            ]);
    }
}

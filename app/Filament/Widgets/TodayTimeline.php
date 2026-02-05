<?php

namespace App\Filament\Widgets;

use App\Models\BookingScheduleItem;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Carbon\Carbon;

class TodayTimeline extends BaseWidget
{
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                BookingScheduleItem::query()
                    ->where('scheduled_date', Carbon::today()->toDateString())
                    ->orderBy('scheduled_time', 'asc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('scheduled_time')
                    ->label('Time')
                    ->time()
                    ->placeholder('Flexible'),
                Tables\Columns\TextColumn::make('title')
                    ->label('Activity / Milestone'),
                Tables\Columns\TextColumn::make('booking.full_name')
                    ->label('Guest'),
                Tables\Columns\TextColumn::make('booking.booking_code')
                    ->label('Code')
                    ->badge()
                    ->color('danger'),
            ]);
    }
}

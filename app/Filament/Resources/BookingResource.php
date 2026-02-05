<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BookingResource\Pages;
use App\Filament\Resources\BookingResource\RelationManagers;
use App\Models\Booking;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class BookingResource extends Resource
{
    protected static ?string $model = Booking::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Core Information')
                    ->schema([
                        Forms\Components\TextInput::make('booking_code')
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpan(1),
                        Forms\Components\Select::make('booking_type')
                            ->options([
                                'room' => 'Room Stay',
                                'tour' => 'Tour Booking',
                                'package' => 'Package Booking',
                            ])
                            ->required()
                            ->reactive(),
                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'confirmed' => 'Confirmed',
                                'cancelled' => 'Cancelled',
                            ])
                            ->required()
                            ->default('pending'),
                        Forms\Components\Select::make('source')
                            ->options([
                                'website' => 'Website',
                                'bookingcom' => 'Booking.com',
                                'walkin' => 'Walk-in',
                            ])
                            ->required()
                            ->default('website'),
                    ])->columns(2),

                Forms\Components\Section::make('Guest Details')
                    ->schema([
                        Forms\Components\TextInput::make('full_name')->required(),
                        Forms\Components\TextInput::make('whatsapp')->required()->tel(),
                        Forms\Components\TextInput::make('email')->email(),
                        Forms\Components\Textarea::make('notes')->columnSpanFull(),
                    ])->columns(2),

                // Detail Sections
                Forms\Components\Section::make('Room Details')
                    ->relationship('roomDetail')
                    ->visible(fn (callable $get) => $get('booking_type') === 'room')
                    ->schema([
                        Forms\Components\DatePicker::make('check_in')->required(),
                        Forms\Components\DatePicker::make('check_out')->required(),
                        Forms\Components\TextInput::make('guests_adults')->numeric()->default(1)->required(),
                        Forms\Components\TextInput::make('guests_children')->numeric()->default(0)->required(),
                    ])->columns(2),

                Forms\Components\Section::make('Tour Details')
                    ->relationship('tourDetail')
                    ->visible(fn (callable $get) => $get('booking_type') === 'tour')
                    ->schema([
                        Forms\Components\Select::make('activity_id')
                            ->relationship('activity', 'title')
                            ->required(),
                        Forms\Components\DatePicker::make('tour_date')->required(),
                        Forms\Components\TimePicker::make('tour_time'),
                        Forms\Components\TextInput::make('guests_adults')->numeric()->default(1)->required(),
                        Forms\Components\TextInput::make('guests_children')->numeric()->default(0)->required(),
                    ])->columns(2),

                Forms\Components\Section::make('Package Details')
                    ->relationship('packageDetail')
                    ->visible(fn (callable $get) => $get('booking_type') === 'package')
                    ->schema([
                        Forms\Components\Select::make('package_id')
                            ->relationship('package', 'title')
                            ->required(),
                        Forms\Components\DatePicker::make('check_in')->required(),
                        Forms\Components\DatePicker::make('check_out')->required(),
                        Forms\Components\TextInput::make('guests_adults')->numeric()->default(1)->required(),
                        Forms\Components\TextInput::make('guests_children')->numeric()->default(0)->required(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('booking_code')
                    ->searchable()
                    ->weight('bold')
                    ->copyable(),
                Tables\Columns\TextColumn::make('booking_type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'room' => 'info',
                        'tour' => 'warning',
                        'package' => 'success',
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'gray',
                        'confirmed' => 'success',
                        'cancelled' => 'danger',
                    }),
                Tables\Columns\TextColumn::make('full_name')
                    ->searchable()
                    ->description(fn (Booking $record): string => $record->whatsapp . ($record->email ? " | {$record->email}" : "")),
                
                Tables\Columns\TextColumn::make('guests')
                    ->getStateUsing(function (Booking $record): string {
                        $details = match ($record->booking_type) {
                            'room' => $record->roomDetail,
                            'tour' => $record->tourDetail,
                            'package' => $record->packageDetail,
                            default => null,
                        };
                        if (!$details) return 'N/A';
                        return "{$details->guests_adults} A, {$details->guests_children} C";
                    }),

                Tables\Columns\TextColumn::make('dates')
                    ->label('Stay / Tour Date')
                    ->getStateUsing(function (Booking $record): string {
                        if ($record->booking_type === 'tour') {
                            return $record->tourDetail?->tour_date . ($record->tourDetail?->tour_time ? " @ {$record->tourDetail?->tour_time}" : "");
                        }
                        $details = $record->booking_type === 'room' ? $record->roomDetail : $record->packageDetail;
                        if (!$details) return 'N/A';
                        return "{$details->check_in} -> {$details->check_out}";
                    }),

                Tables\Columns\TextColumn::make('roomAssignments.room.room_code')
                    ->label('Room')
                    ->badge()
                    ->color('info')
                    ->placeholder('None'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\Filter::make('date_range')
                    ->form([
                        Forms\Components\DatePicker::make('from'),
                        Forms\Components\DatePicker::make('until'),
                    ])
                    ->default([
                        'from' => now()->toDateString(),
                        'until' => now()->toDateString(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->where(function ($q) use ($date, $data) {
                                    $until = $data['until'] ?? $date;
                                    $q->where(function ($sq) use ($date, $until) {
                                        $sq->where('booking_type', 'room')
                                            ->whereHas('roomDetail', fn($q2) => $q2->where('check_in', '<=', $until)->where('check_out', '>', $date));
                                    })->orWhere(function ($sq) use ($date, $until) {
                                        $sq->where('booking_type', 'package')
                                            ->whereHas('packageDetail', fn($q2) => $q2->where('check_in', '<=', $until)->where('check_out', '>', $date));
                                    })->orWhere(function ($sq) use ($date, $until) {
                                        $sq->where('booking_type', 'tour')
                                            ->whereHas('tourDetail', fn($q2) => $q2->whereBetween('tour_date', [$date, $until]));
                                    });
                                })
                            );
                    }),

                Tables\Filters\SelectFilter::make('booking_type')
                    ->options([
                        'room' => 'Room',
                        'tour' => 'Tour',
                        'package' => 'Package',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'confirmed' => 'Confirmed',
                        'cancelled' => 'Cancelled',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('confirm')
                    ->label('Confirm')
                    ->color('success')
                    ->icon('heroicon-o-check')
                    ->hidden(fn (Booking $record) => $record->status === 'confirmed')
                    ->action(fn (Booking $record) => $record->update(['status' => 'confirmed']))
                    ->requiresConfirmation(),
                Tables\Actions\Action::make('cancel')
                    ->label('Cancel')
                    ->color('danger')
                    ->icon('heroicon-o-x-mark')
                    ->hidden(fn (Booking $record) => $record->status === 'cancelled')
                    ->action(fn (Booking $record) => $record->update(['status' => 'cancelled']))
                    ->requiresConfirmation(),
                Tables\Actions\EditAction::make(),
            ])
            ->headerActions([
                Tables\Actions\Action::make('resetToday')
                    ->label('Reset to Today')
                    ->color('gray')
                    ->action(fn () => redirect(static::getUrl('index'))),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ScheduleItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBookings::route('/'),
            'create' => Pages\CreateBooking::route('/create'),
            'edit' => Pages\EditBooking::route('/{record}/edit'),
        ];
    }
}

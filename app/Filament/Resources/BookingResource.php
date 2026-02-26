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

use Illuminate\Database\Eloquent\Model;

class BookingResource extends Resource
{
    protected static ?string $model = Booking::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin', 'staff']) ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin']) ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin', 'staff']) ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin']) ?? false;
    }

    public static function form(Form $form): Form
    {
        $isStaff = auth()->user()?->hasRole('staff') && !auth()->user()?->hasAnyRole(['super_admin', 'admin']);

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
                            ->reactive()
                            ->disabled($isStaff),
                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'confirmed' => 'Confirmed',
                                'cancelled' => 'Cancelled',
                            ])
                            ->required()
                            ->default('pending'),
                    ])->columns(2),

                Forms\Components\Section::make('Financial Info (Reporting Only)')
                    ->schema([
                        Forms\Components\TextInput::make('total_amount')
                            ->numeric()
                            ->prefix('THB')
                            ->readOnly()
                            ->helperText('Calculation handle on server. This is expected revenue.'),
                    ]),
                
                // Detail Sections

                Forms\Components\Section::make('Guest Details')
                    ->schema([
                        Forms\Components\TextInput::make('full_name')->required()->disabled($isStaff),
                        Forms\Components\TextInput::make('whatsapp')->required()->tel()->disabled($isStaff),
                        Forms\Components\TextInput::make('email')->email()->disabled($isStaff),
                        Forms\Components\Textarea::make('notes')->columnSpanFull()->disabled($isStaff),
                    ])->columns(2),

                // Detail Sections
                Forms\Components\Section::make('Room Details')
                    ->relationship('roomDetail')
                    ->visible(fn (callable $get) => $get('booking_type') === 'room')
                    ->schema([
                        Forms\Components\DatePicker::make('check_in')->required()->disabled($isStaff),
                        Forms\Components\DatePicker::make('check_out')->required()->disabled($isStaff),
                        Forms\Components\TextInput::make('guests_adults')->numeric()->default(1)->required()->disabled($isStaff),
                        Forms\Components\TextInput::make('guests_children')->numeric()->default(0)->required()->disabled($isStaff),
                    ])->columns(2),

                Forms\Components\Section::make('Tour Details')
                    ->relationship('tourDetail')
                    ->visible(fn (callable $get) => $get('booking_type') === 'tour')
                    ->schema([
                        Forms\Components\Select::make('activity_id')
                            ->relationship('activity', 'title')
                            ->required()
                            ->disabled($isStaff),
                        Forms\Components\DatePicker::make('tour_date')->required()->disabled($isStaff),
                        Forms\Components\TimePicker::make('tour_time')->disabled($isStaff),
                        Forms\Components\TextInput::make('guests_adults')->numeric()->default(1)->required()->disabled($isStaff),
                        Forms\Components\TextInput::make('guests_children')->numeric()->default(0)->required()->disabled($isStaff),
                    ])->columns(2),

                Forms\Components\Section::make('Package Details')
                    ->relationship('packageDetail')
                    ->visible(fn (callable $get) => $get('booking_type') === 'package')
                    ->schema([
                        Forms\Components\Select::make('package_id')
                            ->relationship('package', 'title')
                            ->required()
                            ->disabled($isStaff),
                        Forms\Components\DatePicker::make('check_in')->required()->disabled($isStaff),
                        Forms\Components\DatePicker::make('check_out')->required()->disabled($isStaff),
                        Forms\Components\TextInput::make('guests_adults')->numeric()->default(1)->required()->disabled($isStaff),
                        Forms\Components\TextInput::make('guests_children')->numeric()->default(0)->required()->disabled($isStaff),
                    ])->columns(2),

                Forms\Components\Section::make('Package Add-ons')
                    ->visible(fn (callable $get) => $get('booking_type') === 'package')
                    ->schema([
                        Forms\Components\CheckboxList::make('packageOptions')
                            ->relationship('packageOptions', 'name')
                            ->columns(2)
                            ->columnSpanFull()
                            ->helperText('Select any add-on options for this package.'),
                    ]),
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
                
                Tables\Columns\TextColumn::make('packageOptions.name')
                    ->label('Selected Options')
                    ->badge()
                    ->color('warning')
                    ->listWithLineBreaks(),
                
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
                
                Tables\Columns\TextColumn::make('total_amount')
                    ->money('THB')
                    ->sortable()
                    ->summarize(Tables\Columns\Summarizers\Sum::make()->money('THB')),
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
                Tables\Filters\TernaryFilter::make('staying')
                    ->label('Staying Now')
                    ->placeholder('All bookings')
                    ->trueLabel('Staying guests')
                    ->falseLabel('Not staying')
                    ->queries(
                        true: fn (Builder $query) => $query->where('status', 'confirmed')
                            ->where(function ($q) {
                                $today = now()->toDateString();
                                $q->whereHas('roomDetail', fn($sq) => $sq->where('check_in', '<=', $today)->where('check_out', '>', $today))
                                  ->orWhereHas('packageDetail', fn($sq) => $sq->where('check_in', '<=', $today)->where('check_out', '>', $today));
                            }),
                        false: fn (Builder $query) => $query,
                    ),
            ])
            ->actions([
                Tables\Actions\Action::make('downloadVoucher')
                    ->label('Download Voucher')
                    ->color('success')
                    ->icon('heroicon-o-document-arrow-down')
                    ->url(fn (Booking $record): string => route('bookings.download-voucher', $record))
                    ->openUrlInNewTab(),
                Tables\Actions\Action::make('confirm')
                    ->label('Confirm & Send Voucher')
                    ->color('success')
                    ->icon('heroicon-o-envelope')
                    ->hidden(fn (Booking $record) => $record->status === 'confirmed')
                    ->requiresConfirmation()
                    ->modalHeading('Confirm Booking')
                    ->modalDescription('This will mark the booking as confirmed and email the guest a confirmation with the PDF voucher attached.')
                    ->action(function (Booking $record) {
                        try {
                            $result = Booking::confirmAndQueueGuestVoucher($record->id);

                            $bookingCode = $result['booking']?->booking_code;

                            if ($result['state'] === 'queued') {
                                \Filament\Notifications\Notification::make()
                                    ->title('Booking confirmed')
                                    ->body("Confirmation email queued for booking {$bookingCode}.")
                                    ->success()
                                    ->send();
                                return;
                            }

                            if ($result['state'] === 'already_sent') {
                                \Filament\Notifications\Notification::make()
                                    ->title('Already processed')
                                    ->body("Confirmation email was already sent for booking {$bookingCode}.")
                                    ->warning()
                                    ->send();
                                return;
                            }

                            if ($result['state'] === 'template_disabled') {
                                \Filament\Notifications\Notification::make()
                                    ->title('Booking confirmed')
                                    ->body('Guest confirmation template is disabled. Enable it to send email.')
                                    ->warning()
                                    ->send();
                                return;
                            }

                            if ($result['state'] === 'no_email') {
                                \Filament\Notifications\Notification::make()
                                    ->title('Booking confirmed')
                                    ->body("No guest email available for booking {$bookingCode}.")
                                    ->warning()
                                    ->send();
                                return;
                            }
                        } catch (\Throwable $e) {
                            \Illuminate\Support\Facades\Log::error("Confirmation email failed for {$record->booking_code}: " . $e->getMessage());
                            \Filament\Notifications\Notification::make()
                                ->title('Failed to send confirmation email.')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                
                Tables\Actions\Action::make('resend_voucher')
                    ->label('Resend Voucher')
                    ->icon('heroicon-o-arrow-path')
                    ->color('gray')
                    ->hidden(fn (Booking $record) => $record->status !== 'confirmed')
                    ->requiresConfirmation()
                    ->action(function (Booking $record) {
                        try {
                            if ($record->email) {
                                $mail = \App\Mail\TemplatedMail::make('booking_confirmation_guest', $record, true);
                                if ($mail) {
                                    \Illuminate\Support\Facades\Mail::to($record->email)->queue($mail);
                                    \Illuminate\Support\Facades\Log::info("Resent confirmation email queued with voucher to {$record->email} (Booking: {$record->booking_code})");
                                    \Filament\Notifications\Notification::make()->title('Voucher resend queued!')->success()->send();
                                } else {
                                    \Filament\Notifications\Notification::make()->title('Email Template not enabled.')->warning()->send();
                                }
                            } else {
                                \Filament\Notifications\Notification::make()->title('No email on file to send voucher.')->warning()->send();
                            }
                        } catch (\Throwable $e) {
                            \Illuminate\Support\Facades\Log::error("Resend confirmation email failed for {$record->booking_code}: " . $e->getMessage());
                            \Filament\Notifications\Notification::make()
                                ->title('Failed to resend email.')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Tables\Actions\Action::make('cancel')
                    ->label('Cancel')
                    ->color('danger')
                    ->icon('heroicon-o-x-mark')
                    ->hidden(fn (Booking $record) => $record->status === 'cancelled')
                    ->action(fn (Booking $record) => $record->update([
                        'status' => 'cancelled',
                    ]))
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

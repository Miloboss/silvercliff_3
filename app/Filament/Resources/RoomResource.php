<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoomResource\Pages;
use App\Models\Room;
use App\Models\RoomType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;

class RoomResource extends Resource
{
    protected static ?string $model = Room::class;
    protected static ?string $navigationIcon = 'heroicon-o-key';
    protected static ?string $navigationGroup = 'Resort Operations';
    protected static ?string $navigationLabel = 'Room Codes';
    protected static ?int $navigationSort = 2;

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin']) ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->schema([
                Forms\Components\Select::make('room_type_id')
                    ->label('Room Type')
                    ->relationship('roomType', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),

                Forms\Components\TextInput::make('room_code')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->placeholder('e.g. D1, OC1, B3, F2'),

                Forms\Components\TextInput::make('sort_order')
                    ->numeric()
                    ->default(0),

                Forms\Components\Toggle::make('is_active')
                    ->default(true)
                    ->required(),

                Forms\Components\Textarea::make('notes')
                    ->columnSpanFull()
                    ->placeholder('Internal notes (not shown to guests)'),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('room_code')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('roomType.name')
                    ->label('Room Type')
                    ->badge()
                    ->color('success')
                    ->sortable(),

                Tables\Columns\TextColumn::make('roomType.code_prefix')
                    ->label('Prefix')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),

                Tables\Columns\TextColumn::make('sort_order')
                    ->numeric()
                    ->sortable(),
            ])
            ->defaultSort('sort_order', 'asc')
            ->filters([
                Tables\Filters\SelectFilter::make('room_type_id')
                    ->label('Room Type')
                    ->relationship('roomType', 'name'),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status')
                    ->boolean()
                    ->trueLabel('Active Only')
                    ->falseLabel('Inactive Only'),
            ])
            ->headerActions([
                // Bulk Generate Rooms action
                Action::make('generateRooms')
                    ->label('Bulk Generate Rooms')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->form([
                        Forms\Components\Select::make('room_type_id')
                            ->label('Room Type')
                            ->options(RoomType::pluck('name', 'id'))
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                $rt = RoomType::find($state);
                                if ($rt) $set('prefix', $rt->code_prefix);
                            }),

                        Forms\Components\TextInput::make('prefix')
                            ->label('Code Prefix')
                            ->placeholder('D')
                            ->helperText('Auto-filled from room type. Override if needed.'),

                        Forms\Components\TextInput::make('start')
                            ->label('Start Number')
                            ->numeric()
                            ->required()
                            ->default(1)
                            ->minValue(1),

                        Forms\Components\TextInput::make('end')
                            ->label('End Number')
                            ->numeric()
                            ->required()
                            ->default(5)
                            ->minValue(1),
                    ])
                    ->action(function (array $data) {
                        $rt     = RoomType::findOrFail($data['room_type_id']);
                        $prefix = $data['prefix'] ?: $rt->code_prefix;
                        $start  = (int) $data['start'];
                        $end    = (int) $data['end'];
                        $created = 0;
                        $skipped = 0;

                        for ($i = $start; $i <= $end; $i++) {
                            $code = $prefix . $i;
                            if (Room::where('room_code', $code)->exists()) {
                                $skipped++;
                                continue;
                            }
                            Room::create([
                                'room_type_id' => $rt->id,
                                'room_code'    => $code,
                                'zone'         => $rt->code_prefix,
                                'sort_order'   => $rt->sort_order + $i,
                                'is_active'    => true,
                            ]);
                            $created++;
                        }

                        Notification::make()
                            ->title("Generated {$created} rooms" . ($skipped ? ", skipped {$skipped} existing" : ''))
                            ->success()
                            ->send();
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListRooms::route('/'),
            'create' => Pages\CreateRoom::route('/create'),
            'edit'   => Pages\EditRoom::route('/{record}/edit'),
        ];
    }
}

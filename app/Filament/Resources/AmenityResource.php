<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AmenityResource\Pages;
use App\Models\Amenity;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AmenityResource extends Resource
{
    protected static ?string $model = Amenity::class;
    protected static ?string $navigationIcon = 'heroicon-o-sparkles';
    protected static ?string $navigationGroup = 'Resort Operations';
    protected static ?string $navigationLabel = 'Amenities';
    protected static ?int $navigationSort = 3;

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin']) ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(100),

                Forms\Components\TextInput::make('key')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(50)
                    ->helperText('Lowercase, no spaces. e.g. wifi, ac, river'),

                Forms\Components\Select::make('icon_key')
                    ->label('Icon (Emoji)')
                    ->options([
                        '❄️'  => '❄️  Air Conditioning',
                        '📶'  => '📶  Wi-Fi',
                        '🚿'  => '🚿  Shower / Bathroom',
                        '🌡️' => '🌡️  Hot Water',
                        '🌿'  => '🌿  Jungle View',
                        '🌊'  => '🌊  River View',
                        '🛏️' => '🛏️  Bed',
                        '🪟'  => '🪟  Terrace / Window',
                        '🛖'  => '🛖  Hammock',
                        '🌬️' => '🌬️  Ceiling Fan',
                        '☕'  => '☕  Coffee / Kettle',
                        '🔒'  => '🔒  In-room Safe',
                        '🪣'  => '🪣  Housekeeping',
                        '🔮'  => '🔮  Unique Design',
                        '🌴'  => '🌴  Jungle Setting',
                        '🏡'  => '🏡  Terrace',
                        '🧴'  => '🧴  Toiletries',
                        '🔦'  => '🔦  Torch',
                        '🧸'  => '🧸  Kids Welcome',
                        '🗄️' => '🗄️  Extra Storage',
                        '🌳'  => '🌳  Treetop View',
                        '👥'  => '👥  Capacity',
                    ])
                    ->searchable()
                    ->required(),

                Forms\Components\TextInput::make('sort_order')
                    ->numeric()
                    ->default(0),

                Forms\Components\Toggle::make('is_active')
                    ->default(true),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('icon_key')
                    ->label('Icon')
                    ->width(60),

                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('key')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('room_types_count')
                    ->label('Used In')
                    ->counts('roomTypes')
                    ->badge()
                    ->color('success'),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),

                Tables\Columns\TextColumn::make('sort_order')
                    ->sortable(),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
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

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListAmenities::route('/'),
            'create' => Pages\CreateAmenity::route('/create'),
            'edit'   => Pages\EditAmenity::route('/{record}/edit'),
        ];
    }
}

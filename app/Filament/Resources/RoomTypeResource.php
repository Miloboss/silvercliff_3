<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoomTypeResource\Pages;
use App\Models\Amenity;
use App\Models\RoomType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class RoomTypeResource extends Resource
{
    protected static ?string $model = RoomType::class;
    protected static ?string $navigationIcon = 'heroicon-o-home-modern';
    protected static ?string $navigationGroup = 'Resort Operations';
    protected static ?string $navigationLabel = 'Room Types';
    protected static ?int $navigationSort = 1;

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin']) ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([

            // ── Basic Info ─────────────────────────────────────
            Forms\Components\Section::make('Basic Information')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(100)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn($state, Forms\Set $set) =>
                            $set('slug', Str::slug($state))
                        ),

                    Forms\Components\TextInput::make('slug')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->helperText('Used in URL: room-details.html?type=slug'),

                    Forms\Components\TextInput::make('code_prefix')
                        ->required()
                        ->maxLength(10)
                        ->label('Code Prefix')
                        ->helperText('e.g. D, OC, B, F — used to generate room codes'),

                    Forms\Components\TextInput::make('base_price_thb')
                        ->numeric()
                        ->required()
                        ->prefix('THB')
                        ->label('Base Price / Night'),

                    Forms\Components\TextInput::make('capacity_adults')
                        ->numeric()
                        ->required()
                        ->default(2)
                        ->minValue(1)
                        ->maxValue(10),

                    Forms\Components\TextInput::make('capacity_children')
                        ->numeric()
                        ->required()
                        ->default(0)
                        ->minValue(0)
                        ->maxValue(10),

                    Forms\Components\TextInput::make('sort_order')
                        ->numeric()
                        ->default(0),

                    Forms\Components\Toggle::make('is_active')
                        ->default(true)
                        ->inline(false),
                ])->columns(2),

            // ── Description ────────────────────────────────────
            Forms\Components\Section::make('Content')
                ->schema([
                    Forms\Components\TextInput::make('subtitle')
                        ->maxLength(255)
                        ->columnSpanFull(),

                    Forms\Components\Textarea::make('description')
                        ->rows(5)
                        ->columnSpanFull(),
                ]),

            // ── Cover Image ────────────────────────────────────
            Forms\Components\Section::make('Cover Image')
                ->schema([
                    Forms\Components\FileUpload::make('cover_image')
                        ->image()
                        ->disk('public')
                        ->directory('room-types/covers')
                        ->imageResizeMode('cover')
                        ->imageCropAspectRatio('16:9')
                        ->maxSize(10240) // 10 MB
                        ->columnSpanFull()
                        ->helperText('Recommended: 1600×900px, max 10MB'),
                ]),

            // ── Gallery Images ─────────────────────────────────
            Forms\Components\Section::make('Gallery Images')
                ->description('Upload multiple images at once. Drag to reorder.')
                ->schema([
                    Forms\Components\Repeater::make('images')
                        ->relationship('images')
                        ->schema([
                            Forms\Components\FileUpload::make('image_path')
                                ->image()
                                ->disk('public')
                                ->directory('room-types/gallery')
                                ->maxSize(10240)
                                ->required()
                                ->label('Image'),

                            Forms\Components\TextInput::make('caption')
                                ->maxLength(255)
                                ->placeholder('Optional caption'),

                            Forms\Components\TextInput::make('sort_order')
                                ->numeric()
                                ->default(0)
                                ->label('Order'),

                            Forms\Components\Toggle::make('is_featured')
                                ->label('Featured (cover fallback)')
                                ->default(false),
                        ])
                        ->columns(2)
                        ->reorderable('sort_order')
                        ->collapsible()
                        ->addActionLabel('Add Image')
                        ->columnSpanFull(),
                ]),

            // ── Highlights ─────────────────────────────────────
            Forms\Components\Section::make('Highlights')
                ->description('Short feature pills shown on the listing card and detail page.')
                ->schema([
                    Forms\Components\Repeater::make('highlights')
                        ->schema([
                            Forms\Components\TextInput::make('icon')
                                ->label('Icon (emoji)')
                                ->placeholder('🌿')
                                ->maxLength(10),

                            Forms\Components\TextInput::make('label')
                                ->label('Label')
                                ->placeholder('Jungle View')
                                ->required()
                                ->maxLength(50),
                        ])
                        ->columns(2)
                        ->reorderable()
                        ->collapsible()
                        ->addActionLabel('Add Highlight')
                        ->columnSpanFull()
                        ->default([]),
                ]),

            // ── Amenities ──────────────────────────────────────
            Forms\Components\Section::make('Amenities')
                ->schema([
                    Forms\Components\CheckboxList::make('amenities')
                        ->relationship('amenities', 'name')
                        ->getOptionLabelFromRecordUsing(fn(Amenity $record) =>
                            "{$record->icon_key}  {$record->name}"
                        )
                        ->columns(3)
                        ->columnSpanFull()
                        ->searchable(),
                ]),

        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('cover_image')
                    ->disk('public')
                    ->label('Cover')
                    ->width(80)
                    ->height(50)
                    ->defaultImageUrl('https://placehold.co/80x50/1b3d17/fff?text=No+Img'),

                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('code_prefix')
                    ->label('Prefix')
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('base_price_thb')
                    ->label('Price/Night')
                    ->money('THB')
                    ->sortable(),

                Tables\Columns\TextColumn::make('rooms_count')
                    ->label('Rooms')
                    ->counts('rooms')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('capacity_adults')
                    ->label('Adults')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),

                Tables\Columns\TextColumn::make('sort_order')
                    ->sortable()
                    ->label('Order'),
            ])
            ->defaultSort('sort_order', 'asc')
            ->reorderable('sort_order')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status')
                    ->boolean()
                    ->trueLabel('Active Only')
                    ->falseLabel('Inactive Only'),
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
            'index'  => Pages\ListRoomTypes::route('/'),
            'create' => Pages\CreateRoomType::route('/create'),
            'edit'   => Pages\EditRoomType::route('/{record}/edit'),
        ];
    }
}

<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PackageResource\Pages;
use App\Filament\Resources\PackageResource\RelationManagers;
use App\Models\Package;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PackageResource extends Resource
{
    protected static ?string $model = Package::class;

    protected static ?string $navigationIcon = 'heroicon-o-archive-box';

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin']) ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        Forms\Components\TextInput::make('code')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, callable $set, callable $get, ?Package $record) {
                                $currentSlug = $get('slug');

                                if ($record || filled($currentSlug) || blank($state)) {
                                    return;
                                }

                                $set('slug', Package::generateUniqueSlug($state));
                            }),
                        Forms\Components\TextInput::make('slug')
                            ->maxLength(255)
                            ->helperText('Auto-generated from title. You can edit this if needed.')
                            ->unique(ignoreRecord: true),
                        Forms\Components\TextInput::make('subtitle')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('price_thb')
                            ->required()
                            ->numeric()
                            ->prefix('฿')
                            ->label('Price (THB)'),
                        Forms\Components\TextInput::make('duration_days')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->label('Duration (Days)'),
                        Forms\Components\TextInput::make('duration_nights')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->label('Duration (Nights)'),
                        Forms\Components\RichEditor::make('description')
                            ->required()
                            ->columnSpanFull()
                            ->toolbarButtons([
                                'bold', 'italic', 'bulletList', 'orderedList', 'link'
                            ]),
                        Forms\Components\Textarea::make('includes')
                            ->rows(3)
                            ->columnSpanFull()
                            ->dehydrateStateUsing(function ($state) {
                                if (blank($state)) {
                                    return [];
                                }

                                if (is_array($state)) {
                                    return array_values(array_filter(array_map('trim', $state), fn ($item) => $item !== ''));
                                }

                                $parts = preg_split('/\r\n|\r|\n|,/', (string) $state);

                                return array_values(array_filter(array_map('trim', $parts), fn ($item) => $item !== ''));
                            })
                            ->helperText('Enter items included in the package (will be stored as JSON array)'),
                        Forms\Components\Toggle::make('is_best_offer')
                            ->label('Mark as Best Offer')
                            ->default(false),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->required(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Package Images')
                    ->description('Upload and manage all package images. Recommended formats: JPG, PNG, WebP. Maximum size: 20MB.')
                    ->schema([
                        Forms\Components\FileUpload::make('thumbnail_image_path')
                            ->label('Package Card Image (Thumbnail)')
                            ->image()
                            ->directory('packages/thumbnails')
                            ->disk('public')
                            ->visibility('public')
                            ->maxSize(20480)
                            ->imageEditor()
                            ->imageEditorAspectRatios([
                                '16:9',
                                '4:3',
                                '1:1',
                            ])
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->helperText('This image appears on package listing cards. Recommended size: 800x600px or 16:9 ratio.')
                            ->columnSpanFull(),

                        Forms\Components\FileUpload::make('hero_image_path')
                            ->label('Hero Image (Details Page Banner)')
                            ->image()
                            ->directory('packages/heroes')
                            ->disk('public')
                            ->visibility('public')
                            ->maxSize(20480)
                            ->imageEditor()
                            ->imageEditorAspectRatios([
                                '21:9',
                                '16:9',
                                '3:1',
                            ])
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->helperText('Large banner image at the top of package details page. Recommended size: 1920x800px or wider.')
                            ->columnSpanFull(),

                        Forms\Components\FileUpload::make('video_path')
                            ->label('Hero Video (Optional)')
                            ->directory('packages/videos')
                            ->disk('public')
                            ->visibility('public')
                            ->maxSize(51200)
                            ->acceptedFileTypes(['video/mp4', 'video/webm'])
                            ->helperText('Optional video for hero section. Max 50MB. MP4 or WebM format recommended.')
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),

                Forms\Components\Section::make('Day Itinerary')
                    ->description('Add daily itinerary with images for each day')
                    ->schema([
                        Forms\Components\Repeater::make('itineraries')
                            ->relationship()
                            ->schema([
                                Forms\Components\TextInput::make('day_no')
                                    ->label('Day Number')
                                    ->required()
                                    ->numeric()
                                    ->minValue(1)
                                    ->default(fn ($get, $livewire) => count($livewire->data['itineraries'] ?? []) + 1),
                                Forms\Components\TextInput::make('title')
                                    ->label('Day Title')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('e.g., Arrival & Beach Relaxation'),
                                Forms\Components\RichEditor::make('description')
                                    ->label('Day Description')
                                    ->required()
                                    ->columnSpanFull()
                                    ->toolbarButtons(['bold', 'italic', 'bulletList', 'orderedList']),
                                Forms\Components\FileUpload::make('image_path')
                                    ->label('Day Image')
                                    ->image()
                                    ->directory('packages/itineraries')
                                    ->disk('public')
                                    ->visibility('public')
                                    ->maxSize(20480)
                                    ->imageEditor()
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                    ->helperText('Image representing this day\'s activities')
                                    ->columnSpanFull(),
                                Forms\Components\Hidden::make('sort_order')
                                    ->default(0),
                            ])
                            ->columns(2)
                            ->reorderable('sort_order')
                            ->orderColumn('sort_order')
                            ->defaultItems(0)
                            ->addActionLabel('Add Day')
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['title'] ?? 'Day ' . ($state['day_no'] ?? ''))
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),

                Forms\Components\Section::make('Package Options & Activities')
                    ->description('Add selectable options or activities (e.g., Day 1 choose any 2 activities)')
                    ->schema([
                        Forms\Components\Repeater::make('options')
                            ->relationship()
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Option Name')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('e.g., Snorkeling Trip'),
                                Forms\Components\Textarea::make('description')
                                    ->label('Description')
                                    ->rows(2)
                                    ->maxLength(65535),
                                Forms\Components\TextInput::make('group_key')
                                    ->label('Group Key')
                                    ->maxLength(255)
                                    ->placeholder('e.g., day1_pick2')
                                    ->helperText('Used to group options (e.g., all Day 1 activities)'),
                                Forms\Components\FileUpload::make('image_path')
                                    ->label('Option Image')
                                    ->image()
                                    ->directory('packages/options')
                                    ->disk('public')
                                    ->visibility('public')
                                    ->maxSize(20480)
                                    ->imageEditor()
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                    ->helperText('Image for this activity/option')
                                    ->columnSpanFull(),
                                Forms\Components\Toggle::make('is_active')
                                    ->label('Active')
                                    ->default(true),
                            ])
                            ->columns(2)
                            ->defaultItems(0)
                            ->addActionLabel('Add Option')
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['name'] ?? 'Option')
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),

                Forms\Components\Section::make('Package Gallery')
                    ->description('Additional images for package gallery (optional)')
                    ->schema([
                        Forms\Components\Repeater::make('media')
                            ->relationship()
                            ->schema([
                                Forms\Components\FileUpload::make('file_path')
                                    ->label('Image/Video')
                                    ->directory('packages/gallery')
                                    ->disk('public')
                                    ->visibility('public')
                                    ->maxSize(20480)
                                    ->image()
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'video/mp4'])
                                    ->helperText('Gallery media file'),
                                Forms\Components\TextInput::make('caption')
                                    ->label('Caption')
                                    ->maxLength(255),
                                Forms\Components\Select::make('type')
                                    ->options([
                                        'image' => 'Image',
                                        'video' => 'Video',
                                    ])
                                    ->default('image')
                                    ->required(),
                                Forms\Components\Hidden::make('sort_order')
                                    ->default(0),
                            ])
                            ->columns(3)
                            ->reorderable('sort_order')
                            ->orderColumn('sort_order')
                            ->defaultItems(0)
                            ->addActionLabel('Add Gallery Item')
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['caption'] ?? 'Gallery Item')
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->searchable(),
                Tables\Columns\TextColumn::make('title')
                    ->searchable(),
                Tables\Columns\TextColumn::make('subtitle')
                    ->searchable(),
                Tables\Columns\TextColumn::make('price_thb')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('duration_days')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('duration_nights')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_best_offer')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                Tables\Columns\ImageColumn::make('thumbnail_image_path')
                    ->label('Thumbnail')
                    ->disk('public'),
                Tables\Columns\ImageColumn::make('hero_image_path')
                    ->label('Hero')
                    ->disk('public')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('preview')
                    ->label('Preview')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Package $record): string => '/simple_web_ui/package-details.html?slug=' . urlencode($record->slug ?: (string) $record->id))
                    ->openUrlInNewTab(),
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
        return [
            RelationManagers\ItinerariesRelationManager::class,
            RelationManagers\OptionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPackages::route('/'),
            'create' => Pages\CreatePackage::route('/create'),
            'edit' => Pages\EditPackage::route('/{record}/edit'),
        ];
    }
}

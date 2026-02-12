<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SiteSettingResource\Pages;
use App\Filament\Resources\SiteSettingResource\RelationManagers;
use App\Models\SiteSetting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SiteSettingResource extends Resource
{
    protected static ?string $model = SiteSetting::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin']) ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('General Information')
                    ->description('Basic setting identification')
                    ->schema([
                        Forms\Components\TextInput::make('label')
                            ->label('Setting Name')
                            ->helperText('The user-friendly name for this setting.')
                            ->required()
                            ->columnSpan(1),
                        Forms\Components\Select::make('group')
                            ->label('Category / Group')
                            ->helperText('Which part of the website this setting belongs to.')
                            ->options([
                                'hero' => '1. Hero Section',
                                'intro' => '2. Welcome / Intro',
                                'activities' => '3. Activities Section',
                                'packages' => '4. Packages Section',
                                'gallery' => '5. Gallery Section',
                                'contact' => '6. Contact & Footer',
                                'global' => '7. Branding & Logo',
                            ])
                            ->required()
                            ->columnSpan(1),
                        Forms\Components\Placeholder::make('current_key')
                            ->label('Internal Identifier')
                            ->content(fn ($record) => $record?->key ?? 'New Record'),
                    ])->columns(2),

                Forms\Components\Section::make('Setting Value')
                    ->description('Edit the actual content here')
                    ->schema([
                        Forms\Components\TextInput::make('value')
                            ->label(fn ($record) => $record?->label ?? 'Value')
                            ->helperText('Enter the text content.')
                            ->visible(fn ($get) => in_array($get('type'), ['text', 'email']))
                            ->required(),
                        Forms\Components\TextInput::make('value')
                            ->label(fn ($record) => $record?->label ?? 'Value')
                            ->helperText('Enter a numeric value (e.g. limit).')
                            ->numeric()
                            ->visible(fn ($get) => $get('type') === 'number')
                            ->afterStateHydrated(function ($component, $state, $get) {
                                if ($get('type') === 'number') {
                                    $component->state((int)$state);
                                }
                            })
                            ->required(),
                        Forms\Components\Textarea::make('value')
                            ->label(fn ($record) => $record?->label ?? 'Description')
                            ->helperText('Multi-line text content.')
                            ->rows(5)
                            ->visible(fn ($get) => $get('type') === 'textarea')
                            ->required(),
                        Forms\Components\FileUpload::make('value')
                            ->label(fn ($record) => $record?->label ?? 'Media File')
                            ->helperText('Upload images (PNG/JPG) or videos (MP4).')
                            ->disk('public')
                            ->directory('settings')
                            ->image()
                            ->visible(fn ($get) => $get('type') === 'file')
                            ->nullable()
                            ->dehydrated(fn ($state) => filled($state))
                            ->getUploadedFileUsing(static function (Forms\Components\FileUpload $component, string $file): ?array {
                                $storage = $component->getDisk();
                                
                                if (str_starts_with($file, './')) {
                                    return [
                                        'name' => basename($file),
                                        'size' => 0,
                                        'type' => 'image/png',
                                        'url' => url($file),
                                    ];
                                }

                                if (! $storage->exists($file)) {
                                    return null;
                                }

                                return [
                                    'name' => basename($file),
                                    'size' => $storage->size($file),
                                    'type' => $storage->mimeType($file),
                                    'url' => $storage->url($file),
                                ];
                            }),
                        Forms\Components\Toggle::make('value')
                            ->label(fn ($record) => $record?->label ?? 'Enabled')
                            ->helperText('Toggle this feature on or off.')
                            ->visible(fn ($get) => $get('type') === 'boolean')
                            ->afterStateHydrated(function ($component, $state, $get) {
                                if ($get('type') === 'boolean') {
                                    $component->state((bool)$state);
                                }
                            })
                            ->dehydrateStateUsing(fn ($state, $get) => $get('type') === 'boolean' ? ($state ? '1' : '0') : $state),
                    ]),

                Forms\Components\Hidden::make('key'),
                Forms\Components\Hidden::make('type'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('label')
                    ->label('Setting Name')
                    ->description(fn ($record) => $record->key)
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('group')
                    ->label('Section')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'hero' => '1. Hero',
                        'intro' => '2. Welcome',
                        'activities' => '3. Activities',
                        'packages' => '4. Packages',
                        'gallery' => '5. Gallery',
                        'contact' => '6. Contact',
                        'global' => '7. Branding',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'hero' => 'primary',
                        'activities' => 'success',
                        'gallery' => 'warning',
                        'contact' => 'info',
                        'global' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('value')
                    ->limit(50)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('group', 'asc')
            ->filters([
                Tables\Filters\SelectFilter::make('group')
                    ->label('Filter by Section')
                    ->options([
                        'hero' => 'Hero',
                        'intro' => 'Intro',
                        'activities' => 'Activities',
                        'packages' => 'Packages',
                        'gallery' => 'Gallery',
                        'contact' => 'Contact',
                        'global' => 'Branding',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSiteSettings::route('/'),
            'create' => Pages\CreateSiteSetting::route('/create'),
            'edit' => Pages\EditSiteSetting::route('/{record}/edit'),
        ];
    }
}

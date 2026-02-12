<?php

namespace App\Filament\Resources\ActivityResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ImagesRelationManager extends RelationManager
{
    protected static string $relationship = 'images';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\FileUpload::make('image_path')
                    ->image()
                    ->directory('activities/gallery')
                    ->maxSize(20480)
                    ->nullable()
                    ->dehydrated(fn ($state) => filled($state)),
                Forms\Components\TextInput::make('sort_order')
                    ->numeric()
                    ->default(0),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('image_path')
            ->columns([
                Tables\Columns\ImageColumn::make('image_path'),
                Tables\Columns\TextColumn::make('sort_order')
                    ->sortable(),
            ])
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Add Single Image'),
                
                Tables\Actions\Action::make('bulkUpload')
                    ->label('Bulk Upload')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('info')
                    ->form([
                        Forms\Components\FileUpload::make('images')
                            ->multiple()
                            ->image()
                            ->directory('activities/gallery')
                            ->maxSize(20480)
                            ->nullable()
                            ->dehydrated(fn ($state) => filled($state))
                            ->maxFiles(10),
                    ])
                    ->action(function (array $data) {
                        foreach ($data['images'] as $imagePath) {
                            $this->getOwnerRecord()->images()->create([
                                'image_path' => $imagePath,
                                'sort_order' => 0,
                            ]);
                        }
                    })
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
}

<?php

namespace App\Filament\Resources\BookingResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ScheduleItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'schedules';

    protected static ?string $title = 'Trip Plan';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('day_no')
                    ->numeric()
                    ->minValue(1),
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->rows(3)
                    ->columnSpanFull(),
                Forms\Components\DatePicker::make('schedule_date'),
                Forms\Components\TextInput::make('schedule_time')
                    ->maxLength(255)
                    ->placeholder('e.g. 09:00 or Time to be confirmed'),
                Forms\Components\TextInput::make('status')
                    ->default('planned')
                    ->maxLength(50),
                Forms\Components\TextInput::make('sort_order')
                    ->numeric()
                    ->minValue(1)
                    ->default(1),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                Tables\Columns\TextColumn::make('day_no')
                    ->label('Day')
                    ->sortable(),
                Tables\Columns\TextColumn::make('schedule_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('schedule_time'),
                Tables\Columns\TextColumn::make('title')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
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

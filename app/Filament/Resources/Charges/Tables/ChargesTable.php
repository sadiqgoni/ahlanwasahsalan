<?php

namespace App\Filament\Resources\Charges\Tables;

use App\Models\Charge;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ChargesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('rate')
                    ->label('Charge')
                    ->formatStateUsing(fn (Charge $record): string => $record->type === 'percent'
                        ? rtrim(rtrim(number_format((float) $record->rate, 2), '0'), '.').'% of order'
                        : '₦'.number_format((float) $record->rate).' per order'),
                TextColumn::make('category.name')
                    ->label('Applies to')
                    ->badge()
                    ->placeholder('All sections'),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

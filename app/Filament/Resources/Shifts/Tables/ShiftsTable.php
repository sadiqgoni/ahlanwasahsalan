<?php

namespace App\Filament\Resources\Shifts\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ShiftsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('opened_at', 'desc')
            ->columns([
                TextColumn::make('user.name')
                    ->label('Cashier')
                    ->searchable(),
                TextColumn::make('opened_at')
                    ->dateTime('d M, h:i A')
                    ->sortable(),
                TextColumn::make('closed_at')
                    ->dateTime('d M, h:i A')
                    ->placeholder('OPEN')
                    ->sortable(),
                TextColumn::make('opening_float')
                    ->label('Float')
                    ->money('NGN'),
                TextColumn::make('expected_cash')
                    ->label('Expected cash')
                    ->money('NGN'),
                TextColumn::make('counted_cash')
                    ->label('Counted cash')
                    ->money('NGN'),
                TextColumn::make('variance')
                    ->money('NGN')
                    ->color(fn ($state): string => $state === null || (float) $state === 0.0
                        ? 'success'
                        : ((float) $state < 0 ? 'danger' : 'warning'))
                    ->weight('bold'),
                TextColumn::make('notes')
                    ->limit(30)
                    ->placeholder('-'),
            ]);
    }
}

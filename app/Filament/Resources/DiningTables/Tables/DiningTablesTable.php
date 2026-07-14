<?php

namespace App\Filament\Resources\DiningTables\Tables;

use App\Models\DiningTable;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DiningTablesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('open_tab')
                    ->label('Open tab')
                    ->state(fn (DiningTable $record): string => $record->openTabTotal() > 0
                        ? '₦'.number_format($record->openTabTotal())
                        : '—')
                    ->badge()
                    ->color(fn (DiningTable $record): string => $record->openTabTotal() > 0 ? 'success' : 'gray'),
                TextColumn::make('pending')
                    ->label('Pending orders')
                    ->state(fn (DiningTable $record): int => $record->pendingOrdersCount())
                    ->badge()
                    ->color(fn (DiningTable $record): string => $record->pendingOrdersCount() > 0 ? 'warning' : 'gray'),
                IconColumn::make('is_active')
                    ->boolean(),
            ])
            ->recordActions([
                Action::make('printQr')
                    ->label('Print QR')
                    ->icon(Heroicon::QrCode)
                    ->color('gray')
                    ->url(fn (DiningTable $record): string => route('table.qr.print', $record))
                    ->openUrlInNewTab(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

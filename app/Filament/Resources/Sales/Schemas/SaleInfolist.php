<?php

namespace App\Filament\Resources\Sales\Schemas;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class SaleInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('receipt_no')
                    ->label('Receipt #'),
                TextEntry::make('created_at')
                    ->label('Time')
                    ->dateTime('d M Y, h:i A'),
                TextEntry::make('user.name')
                    ->label('Cashier'),
                TextEntry::make('total')
                    ->money('NGN'),
                TextEntry::make('payment_method')
                    ->badge(),
                TextEntry::make('payment_reference')
                    ->placeholder('-'),
                TextEntry::make('status')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'voided' ? 'danger' : 'success'),
                TextEntry::make('void_reason')
                    ->placeholder('-'),
                TextEntry::make('voidedBy.name')
                    ->label('Voided by')
                    ->placeholder('-'),
                RepeatableEntry::make('items')
                    ->schema([
                        TextEntry::make('product_name'),
                        TextEntry::make('section')->badge(),
                        TextEntry::make('quantity'),
                        TextEntry::make('options')
                            ->state(fn ($record): string => collect($record->options ?? [])
                                ->map(fn ($o) => $o['name'].' (+₦'.number_format((float) $o['price']).')')
                                ->implode(', ') ?: '—'),
                        TextEntry::make('line_total')->money('NGN'),
                    ])
                    ->columns(5)
                    ->columnSpanFull(),
            ]);
    }
}

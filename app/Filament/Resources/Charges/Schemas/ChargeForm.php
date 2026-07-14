<?php

namespace App\Filament\Resources\Charges\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class ChargeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Charge Details')
                    ->description('Extra amounts added on top of the food price — VAT, service charge, packaging, etc. They appear on the customer receipt as their own line.')
                    ->schema([
                        TextInput::make('name')
                            ->label('Name on receipt')
                            ->placeholder('e.g. VAT (7.5%), Service Charge')
                            ->required(),
                        Select::make('type')
                            ->label('How is it calculated?')
                            ->options([
                                'percent' => 'Percentage of the order (%)',
                                'fixed' => 'Fixed amount per order (₦)',
                            ])
                            ->default('percent')
                            ->live()
                            ->required(),
                        TextInput::make('rate')
                            ->label(fn (Get $get): string => $get('type') === 'fixed' ? 'Amount' : 'Rate')
                            ->numeric()
                            ->required()
                            ->prefix(fn (Get $get): ?string => $get('type') === 'fixed' ? '₦' : null)
                            ->suffix(fn (Get $get): ?string => $get('type') === 'fixed' ? null : '%')
                            ->helperText(fn (Get $get): string => $get('type') === 'fixed'
                                ? 'Added once per order, e.g. ₦200 packaging.'
                                : 'e.g. 7.5 means 7.5% of the order is added.'),
                        Select::make('category_id')
                            ->label('Which section does it apply to?')
                            ->relationship('category', 'name')
                            ->placeholder('All sections — the whole order')
                            ->helperText('Pick a section (e.g. Chips) to charge only orders from that section. Leave empty to apply to everything.'),
                    ])
                    ->columns(2),

                Section::make('Status')
                    ->schema([
                        Toggle::make('is_active')
                            ->label('Active — currently being charged')
                            ->default(true),
                        TextInput::make('sort')
                            ->label('Sort order on receipt')
                            ->numeric()
                            ->default(0),
                    ])
                    ->columns(2),
            ]);
    }
}

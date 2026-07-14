<?php

namespace App\Filament\Resources\DiningTables\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DiningTableForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Table')
                    ->description('Each table gets its own QR code. Customers scan it to see the menu and order — the QR code and link are generated automatically.')
                    ->schema([
                        TextInput::make('name')
                            ->label('Table name / number')
                            ->placeholder('e.g. Table 5, VIP 1, Garden 2')
                            ->required(),
                        Toggle::make('is_active')
                            ->label('Active — QR code accepts orders')
                            ->default(true),
                    ]),
            ]);
    }
}

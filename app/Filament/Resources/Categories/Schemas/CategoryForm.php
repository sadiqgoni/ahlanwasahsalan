<?php

namespace App\Filament\Resources\Categories\Schemas;

use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Section Details')
                    ->description('A section is a food counter in the restaurant — Rice, Tuwo, Tea, Masa & Chips. Each gets a colour on the POS screen and on kitchen tickets.')
                    ->schema([
                        TextInput::make('name')
                            ->label('Section name')
                            ->placeholder('e.g. Rice')
                            ->required(),
                        ColorPicker::make('color')
                            ->label('Colour on POS screen')
                            ->default('#2563eb'),
                        TextInput::make('sort')
                            ->label('Sort order')
                            ->numeric()
                            ->default(0),
                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }
}

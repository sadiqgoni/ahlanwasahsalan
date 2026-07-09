<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Item Details')
                    ->description('What the customer orders and its base price.')
                    ->schema([
                        TextInput::make('name')
                            ->label('Item name')
                            ->placeholder('e.g. Rice & Beans with Oil & Pepper')
                            ->required(),
                        Select::make('category_id')
                            ->label('Section')
                            ->relationship('category', 'name')
                            ->required(),
                        TextInput::make('price')
                            ->label('Base price')
                            ->required()
                            ->numeric()
                            ->prefix('₦'),
                        TextInput::make('barcode')
                            ->label('Barcode (optional)')
                            ->helperText('For laminated scan cards — leave empty if not used.')
                            ->default(null),
                    ])
                    ->columns(2),

                Section::make('Add-ons & Options')
                    ->description('Extras the customer can add — e.g. Salad +₦200, Extra Meat +₦200. These pop up on the POS screen.')
                    ->schema([
                        Repeater::make('options')
                            ->hiddenLabel()
                            ->relationship('options')
                            ->schema([
                                TextInput::make('group')
                                    ->label('Group')
                                    ->placeholder('e.g. Extras, Meat, Protein')
                                    ->default('Add-ons')
                                    ->required(),
                                TextInput::make('name')
                                    ->label('Option name')
                                    ->placeholder('e.g. Salad')
                                    ->required(),
                                TextInput::make('price')
                                    ->label('Extra price')
                                    ->numeric()
                                    ->prefix('₦')
                                    ->default(0)
                                    ->required(),
                            ])
                            ->columns(3)
                            ->defaultItems(0)
                            ->addActionLabel('Add option')
                            ->reorderable(false),
                    ])
                    ->collapsible(),

                Section::make('Display')
                    ->schema([
                        TextInput::make('sort')
                            ->label('Sort order')
                            ->numeric()
                            ->default(0),
                        Toggle::make('is_active')
                            ->label('Available for sale')
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }
}

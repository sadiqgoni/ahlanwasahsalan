<?php

namespace App\Filament\Resources\Charges;

use App\Filament\Resources\Charges\Pages\CreateCharge;
use App\Filament\Resources\Charges\Pages\EditCharge;
use App\Filament\Resources\Charges\Pages\ListCharges;
use App\Filament\Resources\Charges\Schemas\ChargeForm;
use App\Filament\Resources\Charges\Tables\ChargesTable;
use App\Models\Charge;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ChargeResource extends Resource
{
    protected static ?string $model = Charge::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptPercent;

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?string $modelLabel = 'Charge';

    protected static ?string $pluralModelLabel = 'Charges & Taxes';

    /** Only the owner decides what gets added on top of the food price. */
    public static function canViewAny(): bool
    {
        return auth()->user()->isOwner();
    }

    public static function form(Schema $schema): Schema
    {
        return ChargeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ChargesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCharges::route('/'),
            'create' => CreateCharge::route('/create'),
            'edit' => EditCharge::route('/{record}/edit'),
        ];
    }
}

<?php

namespace App\Filament\Resources\DiningTables;

use App\Filament\Resources\DiningTables\Pages\CreateDiningTable;
use App\Filament\Resources\DiningTables\Pages\EditDiningTable;
use App\Filament\Resources\DiningTables\Pages\ListDiningTables;
use App\Filament\Resources\DiningTables\Schemas\DiningTableForm;
use App\Filament\Resources\DiningTables\Tables\DiningTablesTable;
use App\Models\DiningTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class DiningTableResource extends Resource
{
    protected static ?string $model = DiningTable::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQrCode;

    protected static string|UnitEnum|null $navigationGroup = 'Sales Control';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'Table';

    protected static ?string $navigationLabel = 'Tables & QR Codes';

    /** Only the owner sets up tables — cashiers just work the Table Orders queue. */
    public static function canViewAny(): bool
    {
        return auth()->user()->isOwner();
    }

    public static function form(Schema $schema): Schema
    {
        return DiningTableForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DiningTablesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDiningTables::route('/'),
            'create' => CreateDiningTable::route('/create'),
            'edit' => EditDiningTable::route('/{record}/edit'),
        ];
    }
}

<?php

namespace App\Filament\Resources\DiningTables\Pages;

use App\Filament\Resources\DiningTables\DiningTableResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDiningTable extends EditRecord
{
    protected static string $resource = DiningTableResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

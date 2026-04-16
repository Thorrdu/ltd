<?php

namespace App\Filament\Armurerie\Resources\StockItemResource\Pages;

use App\Filament\Armurerie\Resources\StockItemResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStockItems extends ListRecords
{
    protected static string $resource = StockItemResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}

<?php

namespace App\Filament\Armurerie\Resources\WeaponStockMovementResource\Pages;

use App\Filament\Armurerie\Resources\WeaponStockMovementResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListWeaponStockMovements extends ListRecords
{
    protected static string $resource = WeaponStockMovementResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}

<?php

namespace App\Filament\Armurerie\Resources\WeaponSaleResource\Pages;

use App\Filament\Armurerie\Resources\WeaponSaleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListWeaponSales extends ListRecords
{
    protected static string $resource = WeaponSaleResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}

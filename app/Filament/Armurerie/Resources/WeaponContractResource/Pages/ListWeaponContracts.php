<?php

namespace App\Filament\Armurerie\Resources\WeaponContractResource\Pages;

use App\Filament\Armurerie\Resources\WeaponContractResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListWeaponContracts extends ListRecords
{
    protected static string $resource = WeaponContractResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}

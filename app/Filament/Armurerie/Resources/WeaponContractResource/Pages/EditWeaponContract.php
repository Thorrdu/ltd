<?php

namespace App\Filament\Armurerie\Resources\WeaponContractResource\Pages;

use App\Filament\Armurerie\Resources\WeaponContractResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditWeaponContract extends EditRecord
{
    protected static string $resource = WeaponContractResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}

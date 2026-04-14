<?php

namespace App\Filament\Armurerie\Resources\WeaponStockMovementResource\Pages;

use App\Filament\Armurerie\Resources\WeaponStockMovementResource;
use App\Models\WeaponStock;
use Filament\Resources\Pages\CreateRecord;

class CreateWeaponStockMovement extends CreateRecord
{
    protected static string $resource = WeaponStockMovementResource::class;

    protected function afterCreate(): void
    {
        $record = $this->record;
        $stock = WeaponStock::find($record->weapon_stock_id);
        if ($stock) {
            $stock->increment('quantity', $record->quantity_change);
        }
    }
}

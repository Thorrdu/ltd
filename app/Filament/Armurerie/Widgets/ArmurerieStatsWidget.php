<?php

namespace App\Filament\Armurerie\Widgets;

use App\Models\WeaponContract;
use App\Models\WeaponSale;
use App\Models\WeaponStock;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ArmurerieStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $finishedWeapons = WeaponStock::where('category', 'finished_weapon')->sum('quantity');
        $pendingContracts = WeaponContract::whereIn('status', ['pending', 'in_progress'])->count();
        $totalRevenue = WeaponSale::selectRaw('SUM(quantity * unit_price) as total')->value('total') ?? 0;
        $lowStock = WeaponStock::where('quantity', '<=', 0)
            ->whereIn('category', ['piece', 'raw_material'])
            ->count();

        return [
            Stat::make('Armes en stock', $finishedWeapons)
                ->icon('heroicon-o-wrench-screwdriver')
                ->color('success'),
            Stat::make('Contrats en cours', $pendingContracts)
                ->icon('heroicon-o-document-text')
                ->color('warning'),
            Stat::make('Revenus totaux', number_format($totalRevenue, 0, ',', ' ') . ' €')
                ->icon('heroicon-o-banknotes')
                ->color('success'),
            Stat::make('Matériaux à 0', $lowStock)
                ->icon('heroicon-o-exclamation-triangle')
                ->color($lowStock > 0 ? 'danger' : 'success')
                ->description($lowStock > 0 ? 'Réapprovisionnement nécessaire' : 'Stock OK'),
        ];
    }
}

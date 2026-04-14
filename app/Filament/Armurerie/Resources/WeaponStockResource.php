<?php

namespace App\Filament\Armurerie\Resources;

use App\Filament\Armurerie\Resources\WeaponStockResource\Pages;
use App\Models\WeaponStock;
use App\Models\WeaponStockMovement;
use Filament\Actions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class WeaponStockResource extends Resource
{
    protected static ?string $model = WeaponStock::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-cube';

    protected static string | \UnitEnum | null $navigationGroup = 'Stock';

    protected static ?string $navigationLabel = 'Niveaux de stock';

    protected static ?string $modelLabel = 'Stock';

    protected static ?string $pluralModelLabel = 'Stocks';

    protected static ?int $navigationSort = 1;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('category')->label('Catégorie')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'raw_material' => 'gray',
                        'piece' => 'info',
                        'plan' => 'warning',
                        'finished_weapon' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => WeaponStock::CATEGORIES[$state] ?? $state),
                TextColumn::make('name')->label('Nom')->searchable()->sortable(),
                TextColumn::make('weapon.name')->label('Arme liée')->placeholder('—'),
                TextColumn::make('quantity')->label('Quantité')
                    ->color(fn (int $state): string => $state > 0 ? 'success' : 'danger')
                    ->weight('bold')
                    ->sortable(),
                TextColumn::make('quantity')
                    ->label('Plans physiques')
                    ->formatStateUsing(fn ($record) => $record->category === 'plan' ? floor($record->quantity / 4) . ' plans (' . $record->quantity . ' uses)' : '—')
                    ->color('warning')
                    ->visible(fn () => true),
            ])
            ->defaultSort('category')
            ->groups([
                Tables\Grouping\Group::make('category')
                    ->label('Catégorie')
                    ->getTitleFromRecordUsing(fn ($record) => WeaponStock::CATEGORIES[$record->category] ?? $record->category),
            ])
            ->defaultGroup('category')
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->options(WeaponStock::CATEGORIES),
            ])
            ->actions([
                Actions\Action::make('adjust')
                    ->label('Ajuster')
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->color('warning')
                    ->form([
                        TextInput::make('quantity_change')->label('Quantité (+/-)')
                            ->numeric()->required()
                            ->helperText('Positif = entrée, Négatif = sortie. Pour les plans: indiquez les utilisations.'),
                        Select::make('reason')->label('Raison')
                            ->options(WeaponStockMovement::REASONS)->required(),
                        Textarea::make('notes')->label('Notes')->rows(2),
                    ])
                    ->action(function (WeaponStock $record, array $data) {
                        $change = (int) $data['quantity_change'];
                        $record->increment('quantity', $change);

                        WeaponStockMovement::create([
                            'weapon_stock_id' => $record->id,
                            'quantity_change' => $change,
                            'reason' => $data['reason'],
                            'user_id' => auth()->id(),
                            'notes' => $data['notes'] ?? null,
                            'created_at' => now(),
                        ]);
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWeaponStocks::route('/'),
        ];
    }
}

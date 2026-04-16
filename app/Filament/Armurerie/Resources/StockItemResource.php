<?php

namespace App\Filament\Armurerie\Resources;

use App\Filament\Armurerie\Resources\StockItemResource\Pages;
use App\Models\StockItem;
use App\Models\StockMovement;
use Filament\Actions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class StockItemResource extends Resource
{
    protected static ?string $model = StockItem::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-cube';

    protected static string | \UnitEnum | null $navigationGroup = 'Stock';

    protected static ?string $navigationLabel = 'Articles (stock unifié)';

    protected static ?string $modelLabel = 'Article';

    protected static ?string $pluralModelLabel = 'Articles';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('category')->label('Catégorie')
                ->options(StockItem::CATEGORIES)
                ->required()
                ->searchable(),
            TextInput::make('name')->label('Nom')->required()->maxLength(120),
            TextInput::make('slug')->label('Slug')->required()->maxLength(120)->unique(ignoreRecord: true),
            Select::make('weapon_id')->label('Arme liée')
                ->relationship('weapon', 'name')
                ->searchable()
                ->nullable()
                ->helperText('Uniquement pour les catégories weapon_*'),
            TextInput::make('quantity')->label('Quantité')->numeric()->default(0),
            TextInput::make('default_sell_price')->label('Prix de vente ($)')->numeric()->nullable(),
            TextInput::make('default_purchase_price')->label('Prix d\'achat ($)')->numeric()->nullable(),
            TextInput::make('unit_weight_g')->label('Poids unité (g)')->numeric()->nullable(),
            TextInput::make('sort_order')->label('Ordre')->numeric()->default(0),
            Toggle::make('is_sellable')->label('Vendable via /ventes')->default(true),
            Toggle::make('is_active')->label('Actif')->default(true),
            Textarea::make('notes')->label('Notes')->rows(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('category')->label('Catégorie')
                    ->badge()
                    ->color(fn (string $state): string => StockItem::CATEGORY_COLORS[$state] ?? 'gray')
                    ->formatStateUsing(fn (string $state) => StockItem::CATEGORIES[$state] ?? $state),
                TextColumn::make('name')->label('Nom')->searchable()->sortable(),
                TextColumn::make('weapon.name')->label('Arme')->placeholder('—'),
                TextColumn::make('quantity')->label('Qté')
                    ->color(fn (int $state): string => $state > 0 ? 'success' : ($state < 0 ? 'danger' : 'warning'))
                    ->weight('bold')
                    ->sortable(),
                TextColumn::make('default_sell_price')->label('PV')->money('usd', divideBy: 1)->placeholder('—'),
                IconColumn::make('is_sellable')->label('Vendable')->boolean(),
                IconColumn::make('is_active')->label('Actif')->boolean(),
            ])
            ->defaultSort('category')
            ->groups([
                Tables\Grouping\Group::make('category')
                    ->label('Catégorie')
                    ->getTitleFromRecordUsing(fn ($record) => StockItem::CATEGORIES[$record->category] ?? $record->category),
            ])
            ->defaultGroup('category')
            ->filters([
                Tables\Filters\SelectFilter::make('category')->options(StockItem::CATEGORIES),
                Tables\Filters\TernaryFilter::make('is_active')->label('Actifs'),
            ])
            ->actions([
                Actions\EditAction::make(),
                Actions\Action::make('adjust')
                    ->label('Ajuster')
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->color('warning')
                    ->form([
                        TextInput::make('quantity_change')->label('Quantité (+/-)')
                            ->numeric()->required()
                            ->helperText('Positif = entrée, Négatif = sortie.'),
                        Select::make('reason')->label('Raison')
                            ->options(StockMovement::REASONS)->required(),
                        Textarea::make('notes')->label('Notes')->rows(2),
                    ])
                    ->action(function (StockItem $record, array $data) {
                        $change = (int) $data['quantity_change'];
                        $record->increment('quantity', $change);

                        StockMovement::create([
                            'stock_item_id'   => $record->id,
                            'quantity_change' => $change,
                            'reason'          => $data['reason'],
                            'user_id'         => auth()->id(),
                            'notes'           => $data['notes'] ?? null,
                            'created_at'      => now(),
                        ]);
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListStockItems::route('/'),
            'create' => Pages\CreateStockItem::route('/create'),
            'edit'   => Pages\EditStockItem::route('/{record}/edit'),
        ];
    }
}

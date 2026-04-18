<?php

namespace App\Filament\Armurerie\Resources;

use App\Filament\Armurerie\Resources\StockMovementResource\Pages;
use App\Models\StockItem;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\WeaponContract;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class StockMovementResource extends Resource
{
    protected static ?string $model = StockMovement::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static string | \UnitEnum | null $navigationGroup = 'Stock';

    protected static ?string $navigationLabel = 'Mouvements';

    protected static ?string $modelLabel = 'Mouvement';

    protected static ?string $pluralModelLabel = 'Mouvements';

    protected static ?int $navigationSort = 2;

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('stock_item_id')->label('Article')
                ->options(StockItem::orderBy('category')->orderBy('sort_order')->pluck('name', 'id'))
                ->searchable()
                ->required(),
            TextInput::make('quantity_change')->label('Quantité (+/-)')
                ->numeric()->required()
                ->helperText('Positif = entrée, Négatif = sortie'),
            Select::make('reason')->label('Raison')
                ->options(StockMovement::REASONS)
                ->required()
                ->live(),
            TextInput::make('unit_cost')->label('Coût unitaire ($)')->numeric()->nullable()
                ->visible(fn ($get) => ! in_array($get('reason'), ['attribution', 'adjustment'])),
            Select::make('weapon_contract_id')->label('Contrat lié')
                ->options(WeaponContract::orderBy('created_at', 'desc')->pluck('name', 'id'))
                ->searchable()
                ->nullable(),
            Select::make('attributed_to_user_id')->label('Attribué à')
                ->options(User::pluck('name', 'id'))
                ->searchable()
                ->nullable()
                ->visible(fn () => auth()->user()?->isOfficer())
                ->helperText('Seuls les officiers peuvent attribuer à un autre membre'),
            Textarea::make('notes')->label('Notes')->rows(2),
            Hidden::make('user_id')->default(fn () => auth()->id()),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->label('Date')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('stockItem.name')->label('Article')->searchable(),
                TextColumn::make('stockItem.category')->label('Catégorie')
                    ->badge()
                    ->color(fn (string $state): string => StockItem::CATEGORY_COLORS[$state] ?? 'gray')
                    ->formatStateUsing(fn (string $state) => StockItem::CATEGORIES[$state] ?? $state),
                TextColumn::make('quantity_change')->label('Qté')
                    ->color(fn (int $state): string => $state > 0 ? 'success' : 'danger')
                    ->formatStateUsing(fn (int $state) => ($state > 0 ? '+' : '') . $state)
                    ->weight('bold'),
                TextColumn::make('reason')->label('Raison')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'purchase', 'gather'         => 'success',
                        'craft_consume'              => 'danger',
                        'craft_produce'              => 'info',
                        'sale', 'delivery'           => 'warning',
                        'attribution'                => 'info',
                        default                      => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => StockMovement::REASONS[$state] ?? $state),
                TextColumn::make('contract.name')->label('Contrat')->placeholder('—'),
                TextColumn::make('user.name')->label('Par'),
                TextColumn::make('attributedTo.name')->label('Attribué à')->placeholder('—'),
                TextColumn::make('notes')->label('Notes')->limit(30)->placeholder('—'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('reason')->options(StockMovement::REASONS),
                Tables\Filters\SelectFilter::make('stock_item_id')
                    ->label('Article')
                    ->options(StockItem::pluck('name', 'id')),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListStockMovements::route('/'),
            'create' => Pages\CreateStockMovement::route('/create'),
        ];
    }
}

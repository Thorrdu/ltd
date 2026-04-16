<?php

namespace App\Filament\Armurerie\Resources;

use App\Filament\Armurerie\Resources\WeaponResource\Pages;
use App\Models\Weapon;
use Filament\Actions;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class WeaponResource extends Resource
{
    protected static ?string $model = Weapon::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static string | \UnitEnum | null $navigationGroup = 'Armes';

    protected static ?string $navigationLabel = 'Définitions';

    protected static ?string $modelLabel = 'Arme';

    protected static ?string $pluralModelLabel = 'Armes';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informations')->components([
                TextInput::make('name')->label('Nom')->required()->maxLength(100),
                TextInput::make('slug')->label('Slug')->required()->unique(ignoreRecord: true)->maxLength(50)
                    ->helperText('Identifiant unique (ex: wn29, ceramic)'),
                TextInput::make('craft_time_seconds')->label('Temps de craft (sec)')->numeric()->nullable(),
                TextInput::make('sell_price')->label('Prix vente réf. (€)')->numeric()->nullable()->suffix('€'),
                TextInput::make('reference_purchase_price')->label('Prix achat réf. (€)')->numeric()->nullable()->suffix('€')
                    ->helperText('Réservé au SNS (arme non craftée, acquise telle quelle). Laisser vide pour les armes craftées.'),
                Toggle::make('is_active')->label('Actif')->default(true),
                TextInput::make('sort_order')->label('Ordre')->numeric()->default(0),
            ])->columns(2),
            Section::make('Recette de craft')->components([
                Grid::make(4)->components([
                    TextInput::make('recipe_plans')->label('Plans')->numeric()->default(1)->required(),
                    TextInput::make('recipe_ressort')->label('Ressorts')->numeric()->default(0)->required(),
                    TextInput::make('recipe_canon')->label('Canons')->numeric()->default(0)->required(),
                    TextInput::make('recipe_poignee')->label('Poignées')->numeric()->default(0)->required(),
                    TextInput::make('recipe_corp')->label('Corps')->numeric()->default(0)->required(),
                    TextInput::make('recipe_metal')->label('Pièces métal')->numeric()->default(0)->required(),
                    TextInput::make('recipe_polymere')->label('Polymères')->numeric()->default(0)->required(),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nom')->searchable()->sortable(),
                TextColumn::make('craft_time_seconds')->label('Temps')->suffix(' sec')->placeholder('N/A'),
                TextColumn::make('sell_price')->label('Vente réf.')->formatStateUsing(fn ($state) => $state === null ? '—' : number_format((int) $state, 0, ',', ' ') . ' €'),
                TextColumn::make('reference_purchase_price')->label('Achat réf.')->formatStateUsing(fn ($state) => $state === null ? '—' : number_format((int) $state, 0, ',', ' ') . ' €'),
                TextColumn::make('recipe_metal')->label('Métal'),
                TextColumn::make('recipe_polymere')->label('Poly.'),
                TextColumn::make('recipe_ressort')->label('Ress.'),
                IconColumn::make('is_active')->label('Actif')->boolean(),
                TextColumn::make('sort_order')->label('Ordre')->sortable(),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->actions([
                Actions\EditAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([Actions\DeleteBulkAction::make()]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWeapons::route('/'),
            'create' => Pages\CreateWeapon::route('/create'),
            'edit' => Pages\EditWeapon::route('/{record}/edit'),
        ];
    }
}

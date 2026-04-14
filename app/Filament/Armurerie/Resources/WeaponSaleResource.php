<?php

namespace App\Filament\Armurerie\Resources;

use App\Filament\Armurerie\Resources\WeaponSaleResource\Pages;
use App\Models\User;
use App\Models\Weapon;
use App\Models\WeaponContract;
use App\Models\WeaponSale;
use Filament\Actions;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class WeaponSaleResource extends Resource
{
    protected static ?string $model = WeaponSale::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-banknotes';

    protected static string | \UnitEnum | null $navigationGroup = 'Finance';

    protected static ?string $modelLabel = 'Vente';

    protected static ?string $pluralModelLabel = 'Ventes';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('weapon_id')->label('Arme')
                ->options(Weapon::active()->pluck('name', 'id'))
                ->searchable()
                ->required(),
            TextInput::make('quantity')->label('Quantité')->numeric()->required()->minValue(1),
            TextInput::make('unit_price')->label('Prix unitaire (€)')->numeric()->required()->minValue(0),
            TextInput::make('buyer_name')->label('Acheteur')->required()->maxLength(100),
            Select::make('weapon_contract_id')->label('Contrat lié')
                ->options(WeaponContract::where('status', '!=', 'cancelled')->pluck('name', 'id'))
                ->searchable()
                ->nullable(),
            Select::make('sold_by_user_id')->label('Vendu par')
                ->options(User::pluck('name', 'id'))
                ->searchable()
                ->nullable()
                ->visible(fn () => auth()->user()?->isOfficer())
                ->helperText('Par défaut : vous-même'),
            Textarea::make('notes')->label('Notes')->rows(2),
            Hidden::make('user_id')->default(fn () => auth()->id()),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->label('Date')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('weapon.name')->label('Arme')->searchable(),
                TextColumn::make('quantity')->label('Qté'),
                TextColumn::make('unit_price')->label('Prix unit.')->suffix(' €'),
                TextColumn::make('total')->label('Total')
                    ->suffix(' €')
                    ->weight('bold')
                    ->color('success'),
                TextColumn::make('buyer_name')->label('Acheteur')->searchable(),
                TextColumn::make('contract.name')->label('Contrat')->placeholder('—'),
                TextColumn::make('user.name')->label('Enregistré par'),
                TextColumn::make('soldBy.name')->label('Vendu par')->placeholder('='),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('weapon_id')
                    ->label('Arme')
                    ->options(Weapon::pluck('name', 'id')),
            ])
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
            'index' => Pages\ListWeaponSales::route('/'),
            'create' => Pages\CreateWeaponSale::route('/create'),
            'edit' => Pages\EditWeaponSale::route('/{record}/edit'),
        ];
    }
}

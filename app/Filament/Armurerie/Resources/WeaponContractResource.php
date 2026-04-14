<?php

namespace App\Filament\Armurerie\Resources;

use App\Filament\Armurerie\Resources\WeaponContractResource\Pages;
use App\Filament\Armurerie\Resources\WeaponContractResource\RelationManagers\ItemsRelationManager;
use App\Models\WeaponContract;
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

class WeaponContractResource extends Resource
{
    protected static ?string $model = WeaponContract::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-document-text';

    protected static string | \UnitEnum | null $navigationGroup = 'Contrats';

    protected static ?string $modelLabel = 'Contrat';

    protected static ?string $pluralModelLabel = 'Contrats';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Nom du contrat')->required()->maxLength(100),
            TextInput::make('client_name')->label('Client')->required()->maxLength(100),
            Select::make('status')->label('Statut')
                ->options(WeaponContract::STATUSES)
                ->default('pending')
                ->required(),
            Textarea::make('notes')->label('Notes')->rows(3),
            Hidden::make('created_by_user_id')->default(fn () => auth()->id()),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Contrat')->searchable()->sortable(),
                TextColumn::make('client_name')->label('Client')->searchable(),
                TextColumn::make('status')->label('Statut')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'in_progress' => 'info',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => WeaponContract::STATUSES[$state] ?? $state),
                TextColumn::make('progress')->label('Progression')
                    ->suffix('%')
                    ->color(fn ($record) => $record->progress >= 100 ? 'success' : 'warning'),
                TextColumn::make('createdBy.name')->label('Créé par'),
                TextColumn::make('created_at')->label('Date')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(WeaponContract::STATUSES),
            ])
            ->actions([
                Actions\EditAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([Actions\DeleteBulkAction::make()]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            ItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWeaponContracts::route('/'),
            'create' => Pages\CreateWeaponContract::route('/create'),
            'edit' => Pages\EditWeaponContract::route('/{record}/edit'),
        ];
    }
}

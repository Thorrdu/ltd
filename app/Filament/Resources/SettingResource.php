<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SettingResource\Pages;
use App\Models\Setting;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class SettingResource extends Resource
{
    protected static ?string $model = Setting::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static string | \UnitEnum | null $navigationGroup = 'Configuration';
    protected static ?string $modelLabel = 'Paramètre';
    protected static ?string $pluralModelLabel = 'Paramètres';
    protected static ?int $navigationSort = 99;

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user && $user->isAtLeast('treasurer');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\Select::make('group')
                ->label('Groupe')
                ->options(Setting::GROUPS)
                ->required()
                ->searchable(),
            Forms\Components\TextInput::make('key')
                ->label('Clé')
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(255),
            Forms\Components\TextInput::make('label')
                ->label('Libellé')
                ->required()
                ->maxLength(255),
            Forms\Components\Select::make('type')
                ->label('Type')
                ->options(Setting::TYPES)
                ->required()
                ->default('integer'),
            Forms\Components\TextInput::make('value')
                ->label('Valeur')
                ->required()
                ->maxLength(1000),
            Forms\Components\Textarea::make('description')
                ->label('Description')
                ->rows(2)
                ->maxLength(500),
            Forms\Components\TextInput::make('sort_order')
                ->label('Ordre')
                ->numeric()
                ->default(0),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('group')
                    ->label('Groupe')
                    ->formatStateUsing(fn (string $state) => Setting::GROUPS[$state] ?? $state)
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('label')
                    ->label('Paramètre')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextInputColumn::make('value')
                    ->label('Valeur')
                    ->afterStateUpdated(fn () => Setting::clearCache()),
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->formatStateUsing(fn (string $state) => Setting::TYPES[$state] ?? $state)
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'integer' => 'info',
                        'float' => 'warning',
                        'string' => 'gray',
                        'boolean' => 'success',
                        'json' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('group')
                    ->label('Groupe')
                    ->options(Setting::GROUPS),
            ])
            ->actions([Actions\EditAction::make()])
            ->bulkActions([])
            ->defaultSort('group')
            ->groups([
                Tables\Grouping\Group::make('group')
                    ->label('Groupe')
                    ->getTitleFromRecordUsing(fn ($record) => Setting::GROUPS[$record->group] ?? $record->group),
            ])
            ->defaultGroup('group');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSettings::route('/'),
            'create' => Pages\CreateSetting::route('/create'),
            'edit' => Pages\EditSetting::route('/{record}/edit'),
        ];
    }
}

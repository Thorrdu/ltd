<?php
namespace App\Filament\Resources\EnterpriseGroupResource\Pages;
use App\Filament\Resources\EnterpriseGroupResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEnterpriseGroups extends ListRecords
{
    protected static string $resource = EnterpriseGroupResource::class;
    protected function getHeaderActions(): array { return [Actions\CreateAction::make()]; }
}

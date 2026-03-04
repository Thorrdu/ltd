<?php
namespace App\Filament\Resources\EnterpriseGroupResource\Pages;
use App\Filament\Resources\EnterpriseGroupResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEnterpriseGroup extends EditRecord
{
    protected static string $resource = EnterpriseGroupResource::class;
    protected function getHeaderActions(): array { return [Actions\DeleteAction::make()]; }
}

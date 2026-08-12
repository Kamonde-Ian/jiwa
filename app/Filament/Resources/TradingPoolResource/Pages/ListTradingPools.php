<?php

namespace App\Filament\Resources\TradingPoolResource\Pages;

use App\Filament\Resources\TradingPoolResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTradingPools extends ListRecords
{
    protected static string $resource = TradingPoolResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
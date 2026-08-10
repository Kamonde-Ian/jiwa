<?php

namespace App\Filament\Widgets;

use App\Models\PlatformWallet;
use Filament\Widgets\Widget;

class PlatformWalletsWidget extends Widget
{
    protected static ?int $sort = 0;

    protected static string $view = 'filament.widgets.platform-wallets';

    protected int | string | array $columnSpan = 'full';

    protected function getViewData(): array
    {
        return [
            'wallets' => PlatformWallet::orderBy('type')->get(),
        ];
    }
}

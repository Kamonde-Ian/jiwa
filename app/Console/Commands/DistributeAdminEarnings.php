<?php

namespace App\Console\Commands;

use App\Domain\AdminEarnings\AdminEarningsService;
use Illuminate\Console\Command;

class DistributeAdminEarnings extends Command
{
    protected $signature = 'admin:distribute-earnings';

    protected $description = 'Automatically distribute platform admin earnings from completed transactions';

    public function handle(AdminEarningsService $service): int
    {
        $result = $service->distributePending();

        $this->info(number_format($result['admins']) . ' admin(s) eligible for earnings.');
        $this->info("Deposits processed: {$result['deposits']}");
        $this->info("Withdrawals processed: {$result['withdrawals']}");
        $this->info("Investments processed: {$result['investments']}");
        $this->info('Total distributed: $' . number_format($result['total'], 2));

        return self::SUCCESS;
    }
}
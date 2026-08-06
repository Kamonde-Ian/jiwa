<?php

namespace App\Console\Commands;

use App\Domain\Investments\InvestmentService;
use Illuminate\Console\Command;

class CreditDailyInterest extends Command
{
    protected $signature = 'investments:credit-interest';

    protected $description = 'Credit daily interest to active investments and release matured principal';

    public function handle(InvestmentService $service): int
    {
        $credited = $service->creditDailyInterest();
        $matured = $service->processMaturities();

        $this->info("Interest credits applied: {$credited}");
        $this->info("Investments matured: {$matured}");

        return self::SUCCESS;
    }
}

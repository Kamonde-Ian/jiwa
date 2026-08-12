<?php

namespace App\Console\Commands;

use App\Domain\Trading\TradingBotService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RunTradingBot extends Command
{
    protected $signature = 'trading:run-bot';

    protected $description = 'Settle the daily bot trading cycle and pay pool participants their generated returns';

    public function handle(TradingBotService $service): int
    {
        $result = $service->runDailyCycle();

        $this->info("Bot sessions settled: {$result['settled']}");
        $this->info("Participant payouts: {$result['paid']}");
        $this->info("Total payout: \${$result['pnl']}");

        Log::channel('daily')->info('trading:run-bot', $result);

        return self::SUCCESS;
    }
}
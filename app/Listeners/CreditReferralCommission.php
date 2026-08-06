<?php

namespace App\Listeners;

use App\Domain\Referrals\ReferralService;
use App\Events\InvestmentCreated;

class CreditReferralCommission
{
    public function handle(InvestmentCreated $event): void
    {
        app(ReferralService::class)->creditCommission($event->investment);
    }
}

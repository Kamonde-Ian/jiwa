<?php

use Illuminate\Support\Facades\Event;
use App\Events\InvestmentCreated;
use App\Listeners\CreditReferralCommission;

Event::listen(
    InvestmentCreated::class,
    CreditReferralCommission::class,
);

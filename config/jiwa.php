<?php

/*
| Platform-wide business defaults. These values are overridable from the admin
| Settings panel (Phase 6). Never hardcode business numbers in application code
| — read them from config('jiwa...') so admins can change them without a deploy.
*/

return [

    /*
     * Default daily interest rate (0.005 = 0.5%). New investments snapshot this
     * value at creation time; changing it does not affect existing investments.
     */
    'default_daily_rate' => env('JIWA_DEFAULT_DAILY_RATE', 0.005),

    /*
     * Minimum investment amount.
     */
    'min_investment' => env('JIWA_MIN_INVESTMENT', 100),

    /*
     * Referral commission rate (spec conflict: 3% vs 5% — admin-configurable).
     */
    'referral_commission_rate' => env('JIWA_REFERRAL_COMMISSION_RATE', 0.03),

    /*
     * A referrer must have an active investment of at least this amount to
     * unlock their referral link and qualify for commissions.
     */
    'referral_qualification_minimum' => env('JIWA_REFERRAL_QUALIFICATION_MIN', 100),

    /*
     * Interest is credited every N hours.
     */
    'interest_credit_hours' => env('JIWA_INTEREST_CREDIT_HOURS', 24),

    /*
     * Where matured principal is released: 'principal' (back to the Principal
     * Wallet, unlocked once no active investments remain) or 'earnings'.
     */
    'matured_principal_destination' => env('JIWA_MATURED_PRINCIPAL_DESTINATION', 'principal'),

    /*
     * Withdrawals above this amount always require manual review regardless of
     * the auto-approval setting.
     */
    'withdrawal_manual_threshold' => env('JIWA_WITHDRAWAL_MANUAL_THRESHOLD', 10000),

    /*
     * Withdrawals up to this amount are auto-approved (queued for payout)
     * without admin review.
     */
    'withdrawal_auto_approve_threshold' => env('JIWA_WITHDRAWAL_AUTO_APPROVE_THRESHOLD', 500),

    /*
     * Minimum withdrawal amount.
     */
    'min_withdrawal' => env('JIWA_MIN_WITHDRAWAL', 20),

    /*
     * Withdrawal fee applied to each payout (flat USD amount, admin editable).
     */
    'withdrawal_fee' => env('JIWA_WITHDRAWAL_FEE', 0),
    /*
     * Country list used on the profile page.
     */
    'countries' => [
        'United States', 'United Kingdom', 'Canada', 'Australia', 'Germany', 'France',
        'Netherlands', 'Switzerland', 'Austria', 'Belgium', 'Sweden', 'Norway',
        'Denmark', 'Finland', 'Ireland', 'Italy', 'Spain', 'Portugal', 'Poland',
        'Czech Republic', 'Romania', 'Bulgaria', 'Greece', 'Hungary', 'Ukraine',
        'United Arab Emirates', 'Saudi Arabia', 'Qatar', 'Kuwait', 'Israel', 'Turkey',
        'India', 'Pakistan', 'Bangladesh', 'Sri Lanka', 'Nepal', 'Indonesia',
        'Malaysia', 'Singapore', 'Thailand', 'Vietnam', 'Philippines', 'Japan',
        'South Korea', 'China', 'Hong Kong', 'Taiwan', 'Brazil', 'Mexico', 'Argentina',
        'Colombia', 'Chile', 'Peru', 'Nigeria', 'Kenya', 'Ghana', 'South Africa',
        'Egypt', 'Morocco', 'Tunisia',
    ],

    /*
     * Supported crypto networks. 'deposit_address' is the platform receiving
     * address shown to users; it is admin-configurable (Phase 6 settings).
     */
    'networks' => [
        'btc' => [
            'name' => 'Bitcoin (BTC)',
            'currency' => 'BTC',
            'deposit_address' => env('JIWA_BTC_ADDRESS', ''),
            'explorer' => 'https://mempool.space/tx/',
            'decimals' => 8,
        ],
        'eth' => [
            'name' => 'Ethereum (ETH)',
            'currency' => 'ETH',
            'deposit_address' => env('JIWA_ETH_ADDRESS', ''),
            'explorer' => 'https://etherscan.io/tx/',
            'decimals' => 18,
        ],
        'usdt_trc20' => [
            'name' => 'USDT (TRC-20)',
            'currency' => 'USDT',
            'deposit_address' => env('JIWA_USDT_TRC20_ADDRESS', ''),
            'explorer' => 'https://tronscan.org/#/transaction/',
            'decimals' => 6,
        ],
        'usdt_erc20' => [
            'name' => 'USDT (ERC-20)',
            'currency' => 'USDT',
            'deposit_address' => env('JIWA_USDT_ERC20_ADDRESS', ''),
            'explorer' => 'https://etherscan.io/tx/',
            'decimals' => 6,
        ],
        'usdt_bep20' => [
            'name' => 'USDT (BEP-20)',
            'currency' => 'USDT',
            'deposit_address' => env('JIWA_USDT_BEP20_ADDRESS', ''),
            'explorer' => 'https://bscscan.com/tx/',
            'decimals' => 6,
        ],
        'bnb' => [
            'name' => 'BNB (BEP-20)',
            'currency' => 'BNB',
            'deposit_address' => env('JIWA_BNB_ADDRESS', ''),
            'explorer' => 'https://bscscan.com/tx/',
            'decimals' => 8,
        ],
    ],

    /*
     * Currency used across the platform.
     */
    'currency' => 'USD',

    /*
     * Minutes of inactivity before a user is logged out (0 disables).
     */
    'session_timeout_minutes' => env('JIWA_SESSION_TIMEOUT_MINUTES', 60),

    /*
     * Minimum password requirements enforced at registration and in admin
     * user creation.
     */
    'password_min_length' => env('JIWA_PASSWORD_MIN_LENGTH', 10),
];

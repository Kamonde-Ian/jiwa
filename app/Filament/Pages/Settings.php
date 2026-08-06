<?php

namespace App\Filament\Pages;

use App\Support\PlatformSettings;
use Filament\Actions\Action;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class Settings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationGroup = 'Administration';

    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.pages.settings';

    public array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->can('manage settings') ?? false;
    }

    public function mount(): void
    {
        $this->form->fill([
            'min_investment' => PlatformSettings::config('jiwa.min_investment'),
            'default_daily_rate' => PlatformSettings::config('jiwa.default_daily_rate'),
            'interest_credit_hours' => PlatformSettings::config('jiwa.interest_credit_hours'),
            'matured_principal_destination' => PlatformSettings::config('jiwa.matured_principal_destination'),
            'referral_commission_rate' => PlatformSettings::config('jiwa.referral_commission_rate'),
            'referral_qualification_minimum' => PlatformSettings::config('jiwa.referral_qualification_minimum'),
            'min_withdrawal' => PlatformSettings::config('jiwa.min_withdrawal'),
            'withdrawal_fee' => PlatformSettings::config('jiwa.withdrawal_fee'),
            'withdrawal_auto_approve_threshold' => PlatformSettings::config('jiwa.withdrawal_auto_approve_threshold'),
            'withdrawal_manual_threshold' => PlatformSettings::config('jiwa.withdrawal_manual_threshold'),
            'btc_address' => PlatformSettings::config('jiwa.networks.btc.deposit_address'),
            'eth_address' => PlatformSettings::config('jiwa.networks.eth.deposit_address'),
            'usdt_trc20_address' => PlatformSettings::config('jiwa.networks.usdt_trc20.deposit_address'),
            'usdt_erc20_address' => PlatformSettings::config('jiwa.networks.usdt_erc20.deposit_address'),
            'usdt_bep20_address' => PlatformSettings::config('jiwa.networks.usdt_bep20.deposit_address'),
            'bnb_address' => PlatformSettings::config('jiwa.networks.bnb.deposit_address'),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Investments')
                    ->description('Applied to new investments; existing investments keep their snapshotted rate.')
                    ->schema([
                        TextInput::make('min_investment')->label('Minimum investment (USD)')->numeric()->required()->minValue(1),
                        TextInput::make('default_daily_rate')->label('Default daily rate (decimal, e.g. 0.005)')->numeric()->required()->minValue(0.000001)->maxValue(1),
                        TextInput::make('interest_credit_hours')->label('Interest credit interval (hours)')->numeric()->required()->minValue(1),
                        TextInput::make('matured_principal_destination')->label('Matured principal destination (principal|earnings)')->required(),
                    ])
                    ->columns(2),
                Section::make('Referrals')
                    ->schema([
                        TextInput::make('referral_commission_rate')->label('Commission rate (decimal, e.g. 0.03 = 3%)')->numeric()->required()->minValue(0)->maxValue(1),
                        TextInput::make('referral_qualification_minimum')->label('Referrer qualification minimum (USD)')->numeric()->required()->minValue(0),
                    ])
                    ->columns(2),
                Section::make('Withdrawals')
                    ->schema([
                        TextInput::make('min_withdrawal')->label('Minimum withdrawal (USD)')->numeric()->required()->minValue(1),
                        TextInput::make('withdrawal_fee')->label('Withdrawal fee (USD)')->numeric()->required()->minValue(0),
                        TextInput::make('withdrawal_auto_approve_threshold')->label('Auto-approve up to (USD)')->numeric()->required()->minValue(0),
                        TextInput::make('withdrawal_manual_threshold')->label('Always manual review above (USD)')->numeric()->required()->minValue(0),
                    ])
                    ->columns(2),
                Section::make('Crypto Deposit Addresses')
                    ->description('Shown to users when funding their accounts.')
                    ->schema([
                        TextInput::make('btc_address')->label('Bitcoin (BTC) address'),
                        TextInput::make('eth_address')->label('Ethereum (ETH) address'),
                        TextInput::make('usdt_trc20_address')->label('USDT (TRC-20) address'),
                        TextInput::make('usdt_erc20_address')->label('USDT (ERC-20) address'),
                        TextInput::make('usdt_bep20_address')->label('USDT (BEP-20) address'),
                        TextInput::make('bnb_address')->label('BNB (BEP-20) address'),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $settings = [
            'min_investment' => ['investments', (float) $data['min_investment']],
            'default_daily_rate' => ['investments', (float) $data['default_daily_rate']],
            'interest_credit_hours' => ['investments', (int) $data['interest_credit_hours']],
            'matured_principal_destination' => ['investments', $data['matured_principal_destination']],
            'referral_commission_rate' => ['referrals', (float) $data['referral_commission_rate']],
            'referral_qualification_minimum' => ['referrals', (float) $data['referral_qualification_minimum']],
            'min_withdrawal' => ['withdrawals', (float) $data['min_withdrawal']],
            'withdrawal_fee' => ['withdrawals', (float) $data['withdrawal_fee']],
            'withdrawal_auto_approve_threshold' => ['withdrawals', (float) $data['withdrawal_auto_approve_threshold']],
            'withdrawal_manual_threshold' => ['withdrawals', (float) $data['withdrawal_manual_threshold']],
            'networks.btc.deposit_address' => ['networks', $data['btc_address'] ?? ''],
            'networks.eth.deposit_address' => ['networks', $data['eth_address'] ?? ''],
            'networks.usdt_trc20.deposit_address' => ['networks', $data['usdt_trc20_address'] ?? ''],
            'networks.usdt_erc20.deposit_address' => ['networks', $data['usdt_erc20_address'] ?? ''],
            'networks.usdt_bep20.deposit_address' => ['networks', $data['usdt_bep20_address'] ?? ''],
            'networks.bnb.deposit_address' => ['networks', $data['bnb_address'] ?? ''],
        ];

        foreach ($settings as $key => [$group, $value]) {
            PlatformSettings::set($key, $value, $group);
        }

        PlatformSettings::flushCache();

        activity('settings')
            ->causedBy(auth()->user())
            ->withProperties(['action' => 'settings_updated', 'keys' => array_keys($settings)])
            ->log('Platform settings updated');

        Notification::make()
            ->title('Settings saved.')
            ->success()
            ->send();
    }

    protected function getActions(): array
    {
        return [
            Action::make('save')
                ->label('Save settings')
                ->icon('heroicon-o-check')
                ->submit('save')
                ->color('primary'),
        ];
    }
}

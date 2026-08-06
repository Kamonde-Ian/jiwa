<?php

namespace App\Livewire;

use App\Domain\Wallets\WalletService;
use App\Domain\Withdrawals\WithdrawalService;
use App\Models\Wallet;
use App\Models\Withdrawal;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.dashboard')]
#[Title('Withdrawals')]
class Withdrawals extends Component
{
    use WithPagination;

    public string $wallet_type = 'earnings';

    public string $network = 'usdt_trc20';

    public ?string $destination_address = null;

    public ?float $amount = null;

    public ?string $otp = null;

    public function rules(): array
    {
        return [
            'wallet_type' => ['required', 'in:principal,earnings,referral'],
            'network' => ['required', 'in:'.implode(',', array_keys(config('jiwa.networks')))],
            'destination_address' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', "min:".config('jiwa.min_withdrawal')],
            'otp' => ['required', 'string', 'size:6'],
        ];
    }

    public function request(WithdrawalService $service)
    {
        $this->validate();

        try {
            $service->request(
                auth()->user(),
                $this->wallet_type,
                (float) $this->amount,
                $this->network,
                trim($this->destination_address),
                trim($this->otp),
            );
        } catch (\InvalidArgumentException $e) {
            $this->addError('otp', $e->getMessage());
            $this->reset('otp');
            return;
        }

        session()->flash('withdrawal_requested', true);
        $this->reset(['destination_address', 'amount', 'otp']);
    }

    public function cancel($id, WithdrawalService $service)
    {
        $withdrawal = Withdrawal::findOrFail($id);

        try {
            $service->cancel($withdrawal, auth()->user());
            session()->flash('withdrawal_cancelled', true);
        } catch (\LogicException $e) {
            $this->addError('cancel', $e->getMessage());
        }
    }

    public function render(WalletService $walletService)
    {
        $user = auth()->user();

        $withdrawable = (float) $walletService->getOrCreate($user, Wallet::TYPE_EARNINGS)->balance
            + (float) $walletService->getOrCreate($user, Wallet::TYPE_REFERRAL)->balance;

        return view('livewire.withdrawals', [
            'networks' => collect(config('jiwa.networks')),
            'wallets' => collect([
                'principal' => 'Principal Wallet',
                'earnings' => 'Earnings Wallet',
                'referral' => 'Referral Wallet',
            ]),
            'withdrawals' => $user->withdrawals()->latest('id')->paginate(10),
            'stats' => [
                'withdrawn' => (float) $user->withdrawals()->where('status', 'completed')->sum('amount'),
                'pending' => $user->withdrawals()->where('status', 'pending_review')->count(),
                'withdrawable' => $withdrawable,
            ],
        ]);
    }
}

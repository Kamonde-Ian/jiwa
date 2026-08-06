<?php

namespace App\Livewire;

use App\Domain\Investments\InvestmentService;
use App\Domain\Wallets\WalletService;
use App\Models\Investment;
use App\Models\InvestmentPlan;
use App\Models\Wallet;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.dashboard')]
#[Title('Invest')]
class Invest extends Component
{
    use WithPagination;

    public int $selectedPlanId = 0;

    public ?float $amount = null;

    public float $principal = 0;

    public function mount(WalletService $walletService): void
    {
        $this->principal = (float) $walletService->getOrCreate(auth()->user(), Wallet::TYPE_PRINCIPAL)->balance;
    }

    public function setAmountPercent(float $percent): void
    {
        $this->amount = round($this->principal * $percent / 100, 2);
    }

    public function rules(): array
    {
        $min = (float) config('jiwa.min_investment');

        return [
            'selectedPlanId' => ['required', 'exists:investment_plans,id'],
            'amount' => ['required', 'numeric', "min:{$min}"],
        ];
    }

    public function invest(InvestmentService $service, WalletService $walletService)
    {
        $this->validate();

        $plan = InvestmentPlan::where('id', $this->selectedPlanId)->where('is_active', true)->firstOrFail();
        $user = auth()->user();

        $principal = $walletService->getOrCreate($user, Wallet::TYPE_PRINCIPAL);

        if ((float) $this->amount > (float) $principal->balance) {
            $this->addError('amount', 'Insufficient principal wallet balance.');
            return;
        }

        try {
            $investment = $service->create($user, $plan, (float) $this->amount);
        } catch (\InvalidArgumentException $e) {
            $this->addError('amount', $e->getMessage());
            return;
        }

        session()->flash('invested', [
            'plan' => $plan->name,
            'amount' => (float) $this->amount,
            'matures_at' => $investment->matures_at->format('M j, Y'),
            'rate' => $plan->daily_rate,
        ]);

        $this->reset(['selectedPlanId', 'amount']);
    }

    public function render()
    {
        $user = auth()->user();

        return view('livewire.invest', [
            'plans' => InvestmentPlan::where('is_active', true)->orderBy('min_amount')->get(),
            'principal' => $this->principal,
            'activeInvestments' => $user->investments()->where('status', Investment::STATUS_ACTIVE)->count(),
            'investments' => $user->investments()->with('plan')->latest('id')->paginate(5),
        ]);
    }
}

<?php

namespace App\Livewire;

use App\Models\InvestmentPlan;
use Livewire\Component;

class PlanCalculator extends Component
{
    public int $selectedPlanId = 0;

    public ?float $amount = null;

    public function getPlansProperty()
    {
        return InvestmentPlan::where('is_active', true)->orderBy('min_amount')->get();
    }

    public function updatedSelectedPlanId(): void
    {
        if ($this->amount === null && $this->selectedPlan) {
            $this->amount = (float) $this->selectedPlan->min_amount;
        }
    }

    public function getSelectedPlanProperty(): ?InvestmentPlan
    {
        if (! $this->selectedPlanId) {
            return null;
        }

        return $this->plans->firstWhere('id', $this->selectedPlanId) ?? null;
    }

    public function getFiguresProperty(): array
    {
        $plan = $this->selectedPlan;

        if (! $plan) {
            return [];
        }

        $amount = $this->amount !== null ? max(0, (float) $this->amount) : 0;
        $rate = (float) $plan->daily_rate;

        return [
            'amount' => $amount,
            'daily' => $amount * $rate,
            'monthly' => $amount * $rate * 30,
            'duration' => $amount * $rate * $plan->duration_days,
            'total' => $amount + ($amount * $rate * $plan->duration_days),
            'annualized' => $rate * 365,
        ];
    }

    public function render()
    {
        return view('livewire.plan-calculator');
    }
}

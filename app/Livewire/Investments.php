<?php

namespace App\Livewire;

use App\Models\Investment;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.dashboard')]
#[Title('My Investments')]
class Investments extends Component
{
    use WithPagination;

    public function render()
    {
        $user = auth()->user();

        $investments = $user->investments()
            ->with('plan')
            ->latest('id')
            ->paginate(10);

        $summary = [
            'total' => (float) $user->investments()->sum('principal_amount'),
            'active' => $user->investments()->where('status', Investment::STATUS_ACTIVE)->count(),
            'active_total' => (float) $user->investments()->where('status', Investment::STATUS_ACTIVE)->sum('principal_amount'),
            'matured' => $user->investments()->where('status', Investment::STATUS_MATURED)->count(),
        ];

        $allocation = $user->investments()
            ->where('status', Investment::STATUS_ACTIVE)
            ->with('plan')
            ->get()
            ->groupBy(fn ($inv) => $inv->plan?->name ?? 'Other')
            ->map(fn ($group) => round((float) $group->sum('principal_amount'), 2));

        $allocationChart = [
            'labels' => $allocation->keys()->all(),
            'values' => $allocation->values()->all(),
        ];

        return view('livewire.investments', [
            'investments' => $investments,
            'summary' => $summary,
            'allocationChart' => $allocationChart,
        ]);
    }
}

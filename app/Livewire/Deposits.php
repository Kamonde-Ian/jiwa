<?php

namespace App\Livewire;

use App\Domain\Deposits\DepositService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.dashboard')]
#[Title('Deposits')]
class Deposits extends Component
{
    use WithPagination;

    public string $network = 'usdt_trc20';

    public ?string $tx_hash = null;

    public ?float $amount_usd = null;

    public function rules(): array
    {
        return [
            'network' => ['required', 'in:'.implode(',', array_keys(config('jiwa.networks')))],
            'tx_hash' => ['required', 'string', 'max:255'],
            'amount_usd' => ['required', 'numeric', 'gt:0'],
        ];
    }

    public function request(DepositService $service)
    {
        $this->validate();

        try {
            $service->request(
                auth()->user(),
                $this->network,
                trim($this->tx_hash),
                (float) $this->amount_usd,
            );
        } catch (\InvalidArgumentException $e) {
            $this->addError('tx_hash', $e->getMessage());
            return;
        }

        session()->flash('deposit_requested', true);
        $this->reset(['tx_hash', 'amount_usd']);
    }

    public function render()
    {
        $networks = collect(config('jiwa.networks'))
            ->map(fn ($network, $key) => [
                ...$network,
                'deposit_address' => \App\Support\PlatformSettings::config("jiwa.networks.{$key}.deposit_address"),
            ]);

        $user = auth()->user();

        return view('livewire.deposits', [
            'networks' => $networks,
            'deposits' => $user->deposits()->latest('id')->paginate(10),
            'stats' => [
                'deposited' => (float) $user->deposits()->where('status', 'confirmed')->sum('amount_usd'),
                'confirmed' => $user->deposits()->where('status', 'confirmed')->count(),
                'pending' => $user->deposits()->where('status', 'pending')->count(),
            ],
        ]);
    }
}

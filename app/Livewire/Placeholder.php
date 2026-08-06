<?php

namespace App\Livewire;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.dashboard')]
class Placeholder extends Component
{
    #[Title('Coming Soon')]
    public string $title = '';

    public string $description = '';

    public function render()
    {
        return view('livewire.placeholder');
    }
}

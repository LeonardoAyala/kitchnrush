<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\User;


class Highscore extends Component
{

    public $count = 0;

    protected $listeners = ['increment'];

    public $players;

    public function mount()
    {
        $this->players = User::all();
    }

    public function increment()
    {
        $this->players = User::orderBy('created_at', 'desc')->take(10)->get();
    }

    public function incrementD()
    {
        $this->count++;
    }


    public function render()
    {
        return view('livewire.highscore');
    }

}

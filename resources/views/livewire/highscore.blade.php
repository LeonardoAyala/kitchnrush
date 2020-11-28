<div>
@csrf

    @foreach($players as $player)
    <p>{{ $player->name }} : time - {{ $player->created_at }} </p>

    @endforeach

    <!--button wire:click="increment">+</button-->
</div>

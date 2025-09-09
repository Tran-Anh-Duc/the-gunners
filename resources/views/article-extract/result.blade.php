<h1>{{ $data['title'] }}</h1>
<div class="prose max-w-none">
    @foreach ($data['ordered'] as $it)
        @if ($it['type']==='text')
            @switch($it['tag'])
                @case('h2') <h2>{{ $it['text'] }}</h2> @break
                @case('h3') <h3>{{ $it['text'] }}</h3> @break
                @default    <p>{{ $it['text'] }}</p>
            @endswitch
        @else
            <figure style="margin:16px 0;">
                <img src="{{ $it['src'] }}" alt="{{ $it['alt'] ?? '' }}" style="max-width:50%;height:auto;">
            </figure>
        @endif
    @endforeach
</div>

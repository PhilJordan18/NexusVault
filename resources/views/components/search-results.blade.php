@if($results->isNotEmpty())
    <ul class="max-h-72 overflow-y-auto">
        @foreach($results as $service)
            <li>
                <a href="{{ route('services.show', $service->name) }}"
                   class="flex items-center gap-3 px-4 py-3 hover:bg-white/10 transition rounded-xl cursor-pointer">
                    @if($service->favicon)
                        <img src="{{ $service->favicon }}" class="w-8 h-8 rounded-lg" alt="">
                    @else
                        <div class="w-8 h-8 bg-white/10 rounded-lg flex items-center justify-center text-sm">
                            {{ strtoupper(substr($service->name, 0, 1)) }}
                        </div>
                    @endif
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium truncate">{{ $service->name }}</p>
                        <p class="text-xs text-white/50 truncate">
                            {{ $service->username }} · {{ $service->url ?? 'No URL' }}
                        </p>
                    </div>
                </a>
            </li>
        @endforeach
    </ul>
@elseif(strlen($query) >= 1)
    <div class="px-4 py-6 text-center text-white/40">No results found.</div>
@endif

@if($results->isNotEmpty())
    <ul class="max-h-72 overflow-y-auto">
        @foreach($results as $service)
            <li>
                <a href="{{ route('services.show', ['name' => $service->name, 'type' => $service->type]) }}"
                   class="flex items-center gap-3 px-4 py-3 hover:bg-white/10 transition rounded-xl cursor-pointer">
                    @if($service->type === \App\Models\Service::TYPE_PAYMENT_CARD)
                        <div class="w-8 h-8 bg-emerald-500/10 rounded-lg flex items-center justify-center text-emerald-400">
                            <i class="fa-solid fa-credit-card text-sm"></i>
                        </div>
                    @elseif($service->type === \App\Models\Service::TYPE_SECURE_NOTE)
                        <div class="w-8 h-8 bg-indigo-500/10 rounded-lg flex items-center justify-center text-indigo-400">
                            <i class="fa-solid fa-note-sticky text-sm"></i>
                        </div>
                    @elseif($service->favicon)
                        <img src="{{ $service->favicon }}" class="w-8 h-8 rounded-lg object-contain bg-white/5 p-1" alt=""
                             onerror="this.onerror=null;this.src='{{ asset('logo/LogoMonogramme.svg') }}';">
                    @else
                        <div class="w-8 h-8 bg-white/10 rounded-lg flex items-center justify-center text-sm">
                            {{ strtoupper(substr($service->name, 0, 1)) }}
                        </div>
                    @endif
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium truncate">{{ $service->name }}</p>
                        <p class="text-xs text-white/50 truncate">
                            {{ $service->type === \App\Models\Service::TYPE_PAYMENT_CARD ? 'Payment card' : ($service->type === \App\Models\Service::TYPE_SECURE_NOTE ? 'Secure note' : $service->username.' · '.($service->url ?? 'No URL')) }}
                        </p>
                    </div>
                </a>
            </li>
        @endforeach
    </ul>
@elseif(strlen($query) >= 1)
    <div class="px-4 py-6 text-center text-white/40">No results found.</div>
@endif

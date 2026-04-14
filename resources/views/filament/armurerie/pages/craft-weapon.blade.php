<x-filament-panels::page>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- LEFT: Form + Preview --}}
        <div class="lg:col-span-2 space-y-4">
            <form wire:submit="craft">
                {{ $this->form }}

                @php $preview = $this->preview; @endphp

                @if($preview)
                    {{-- Recipe --}}
                    <div class="rounded-xl border border-white/10 bg-white/5 p-4 mt-4">
                        <h3 class="text-lg font-bold text-amber-500 mb-1">
                            {{ $preview['qty'] }}× {{ $preview['weapon']->name }}
                        </h3>
                        @if($preview['craftTime'])
                            <p class="text-xs text-gray-500 mb-3">⏱ Temps estimé : {{ gmdate('i\m s\s', $preview['craftTime']) }}</p>
                        @endif

                        {{-- Pieces needed vs stock --}}
                        <div class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Pièces nécessaires</div>
                        <div class="space-y-1.5">
                            @foreach($preview['needs'] as $item)
                                @php
                                    $pct = $item['have'] > 0 ? min(100, round($item['need'] > 0 ? ($item['have'] / $item['need']) * 100 : 100)) : 0;
                                    $barColor = $item['ok'] ? 'bg-emerald-500' : 'bg-red-500';
                                @endphp
                                <div>
                                    <div class="flex justify-between text-sm mb-0.5">
                                        <span class="text-gray-300">
                                            {{ $item['name'] }}
                                            @if(($item['type'] ?? '') === 'plan')
                                                <span class="text-gray-600 text-xs">({{ $item['physical'] }} plans physiques)</span>
                                            @endif
                                        </span>
                                        <span class="font-mono {{ $item['ok'] ? 'text-emerald-400' : 'text-red-400 font-bold' }}">
                                            {{ $item['need'] }} / {{ $item['have'] }}
                                            @if(!$item['ok'])
                                                <span class="text-red-500 text-xs">▲{{ $item['need'] - $item['have'] }}</span>
                                            @else
                                                ✓
                                            @endif
                                        </span>
                                    </div>
                                    <div class="h-1.5 bg-gray-800 rounded-full overflow-hidden">
                                        <div class="{{ $barColor }} h-full rounded-full transition-all" style="width: {{ $pct }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        {{-- Raw materials breakdown --}}
                        <div class="mt-4 pt-3 border-t border-white/10">
                            <div class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Matières premières (si craft from scratch)</div>
                            <div class="grid grid-cols-2 gap-2">
                                @foreach($preview['rawMaterials'] as $raw)
                                    <div class="flex justify-between text-sm px-2 py-1 rounded bg-white/5">
                                        <span class="text-gray-400">{{ $raw['name'] }}</span>
                                        <span class="font-mono {{ $raw['need'] <= $raw['have'] ? 'text-emerald-400' : 'text-red-400' }}">
                                            {{ $raw['need'] }} (stock: {{ $raw['have'] }})
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        {{-- Cost --}}
                        @if($preview['craftBreakdown']['polymere_cost'] > 0)
                            <div class="mt-3 text-center text-amber-500 font-bold text-sm">
                                💰 Coût polymères : {{ number_format($preview['craftBreakdown']['polymere_cost'], 0, ',', ' ') }} €
                            </div>
                        @endif

                        {{-- Craft button --}}
                        <div class="mt-4">
                            @if($preview['canCraft'])
                                <x-filament::button type="submit" color="success" class="w-full" size="lg">
                                    🔨 Crafter {{ $preview['qty'] }}× {{ $preview['weapon']->name }}
                                </x-filament::button>
                            @else
                                <div class="text-center text-red-400 font-semibold py-3 rounded-lg bg-red-500/10 border border-red-500/20">
                                    ⚠ Stock insuffisant — il manque des matériaux
                                </div>
                            @endif
                        </div>
                    </div>
                @else
                    <div class="text-center text-gray-600 py-8 text-sm italic">
                        Sélectionnez une arme pour voir l'aperçu du craft
                    </div>
                @endif
            </form>
        </div>

        {{-- RIGHT: Stock overview --}}
        <div class="space-y-4">
            {{-- Weapons in stock --}}
            <div class="rounded-xl border border-white/10 bg-white/5 p-4">
                <div class="text-xs font-bold text-amber-500 uppercase tracking-wider mb-3">Armes en stock</div>
                <div class="space-y-2">
                    @foreach($this->weaponsOverview as $w)
                        <div class="flex items-center justify-between text-sm px-2 py-1.5 rounded {{ $w['finished'] > 0 ? 'bg-emerald-500/10' : 'bg-white/5' }}">
                            <span class="text-gray-300">{{ $w['name'] }}</span>
                            <div class="text-right">
                                <span class="font-mono font-bold {{ $w['finished'] > 0 ? 'text-emerald-400' : 'text-gray-600' }}">{{ $w['finished'] }}</span>
                                <span class="text-gray-600 text-xs ml-1">({{ $w['plan_physical'] }}p)</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Pieces stock --}}
            <div class="rounded-xl border border-white/10 bg-white/5 p-4">
                <div class="text-xs font-bold text-amber-500 uppercase tracking-wider mb-3">Pièces</div>
                <div class="space-y-1">
                    @foreach($this->piecesStock['pieces'] as $p)
                        <div class="flex justify-between text-xs px-1">
                            <span class="text-gray-400">{{ $p['name'] }}</span>
                            <span class="font-mono {{ $p['qty'] > 0 ? 'text-gray-300' : 'text-red-400' }}">{{ $p['qty'] }}</span>
                        </div>
                    @endforeach
                </div>
                <div class="text-xs font-bold text-amber-500 uppercase tracking-wider mt-3 mb-2">Matières premières</div>
                <div class="space-y-1">
                    @foreach($this->piecesStock['raw'] as $r)
                        <div class="flex justify-between text-xs px-1">
                            <span class="text-gray-400">{{ $r['name'] }}</span>
                            <span class="font-mono text-amber-400 font-bold">{{ $r['qty'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>

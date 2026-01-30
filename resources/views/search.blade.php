@extends('layouts.guest')

@section('content')
<div class="bg-[#242629] min-h-screen font-sans">
    
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        {{-- JUDUL PENCARIAN --}}
        <div class="flex justify-between items-center mb-6 border-b border-gray-600 pb-4">
            <h2 class="text-2xl font-black text-white uppercase tracking-wider">
                @if(request('q'))
                    Results for: "<span class="text-purple-400">{{ request('q') }}</span>"
                @else
                    All Products
                @endif
            </h2>
            <span class="text-gray-400 text-sm font-bold">{{ $games->total() }} titles found</span>
        </div>

        <div class="flex flex-col md:flex-row gap-6">
            
            {{-- KOLOM KIRI: HASIL PENCARIAN (LIST) --}}
            <div class="w-full md:w-3/4">
                
                {{-- Sorting Bar (Simulasi) --}}
                <div class="bg-black/20 p-2 mb-4 flex justify-between items-center text-xs text-gray-400 border border-gray-700 rounded-sm">
                    <span>Sort by: <span class="text-white font-bold cursor-pointer">Relevance</span></span>
                    <span class="cursor-pointer hover:text-white">Customize</span>
                </div>

                <div class="space-y-2">
                    @forelse ($games as $game)
                        <a href="{{ route('game.show', $game) }}" class="flex bg-[#16161a] hover:bg-purple-900 border border-black/50 hover:border-gray-500 transition group h-[120px] overflow-hidden relative shadow-md">
                            
                            {{-- Gambar --}}
                            <div class="w-[240px] flex-shrink-0 relative">
                                <img src="{{ $game->cover_image }}" class="w-full h-full object-cover">
                            </div>

                            {{-- Info --}}
                            <div class="flex-grow p-4 flex flex-col justify-between">
                                <h3 class="text-lg font-bold text-white group-hover:text-blue-400 truncate">{{ $game->title }}</h3>
                                
                                <div class="flex items-center gap-2 text-xs text-gray-500 font-bold">
                                    <span class="uppercase">{{ \Carbon\Carbon::parse($game->release_date)->format('M d, Y') }}</span>
                                    <span>|</span>
                                    <span class="text-gray-400">{{ $game->publisher }}</span>
                                </div>

                                <div class="flex gap-1 mt-1">
                                    <span class="text-[10px] bg-gray-700 text-gray-300 px-1 rounded border border-gray-600">{{ $game->genre }}</span>
                                </div>
                            </div>

                            {{-- Harga --}}
                            <div class="w-32 flex flex-col justify-center items-end pr-6 pl-2 bg-black/10">
                                @if($game->price == 0)
                                    <span class="text-white font-black text-sm">Free to Play</span>
                                @elseif($game->discount_percent > 0)
                                    <div class="flex items-center gap-2">
                                        <span class="bg-[#4c6b22] text-[#a4d007] text-sm font-bold px-1">-{{ $game->discount_percent }}%</span>
                                        <div class="flex flex-col items-end leading-none">
                                            <span class="text-[10px] text-gray-500 line-through">Rp{{ number_format($game->price, 0, ',', '.') }}</span>
                                            <span class="text-[#a4d007] font-bold text-sm">Rp{{ number_format($game->price * (1 - $game->discount_percent/100), 0, ',', '.') }}</span>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-white font-bold text-sm">Rp{{ number_format($game->price, 0, ',', '.') }}</span>
                                @endif
                            </div>
                        </a>
                    @empty
                        <div class="p-8 text-center border-2 border-dashed border-gray-600 rounded">
                            <p class="text-gray-400 text-lg">No games found.</p>
                            <a href="{{ route('games.search') }}" class="text-purple-400 hover:underline mt-2 inline-block">Clear Filters</a>
                        </div>
                    @endforelse

                    {{-- Pagination --}}
                    <div class="mt-6">
                        {{ $games->links() }}
                    </div>
                </div>
            </div>

            {{-- KOLOM KANAN: FILTER (SIDEBAR) --}}
            <div class="w-full md:w-1/4 flex-shrink-0">
                <div class="bg-[#16161a] border border-gray-600 rounded-sm p-4 sticky top-24">
                    
                    {{-- Narrow by Price --}}
                    <div class="mb-6">
                        <h3 class="font-bold text-white text-sm mb-3 uppercase tracking-wide border-b border-gray-600 pb-1">Narrow by Price</h3>
                        <div class="space-y-2 text-sm text-[#556772]">
                            <a href="{{ route('games.search', array_merge(request()->all(), ['price' => 'free'])) }}" class="block hover:text-white hover:bg-gray-700 p-1 rounded cursor-pointer transition {{ request('price') == 'free' ? 'text-[#7f5af0] font-bold' : '' }}">
                                <span class="inline-block w-4 text-center">{{ request('price') == 'free' ? '✔' : '' }}</span> Free to Play
                            </a>
                            <a href="{{ route('games.search', array_merge(request()->all(), ['price' => 'paid'])) }}" class="block hover:text-white hover:bg-gray-700 p-1 rounded cursor-pointer transition {{ request('price') == 'paid' ? 'text-[#7f5af0] font-bold' : '' }}">
                                <span class="inline-block w-4 text-center">{{ request('price') == 'paid' ? '✔' : '' }}</span> Paid
                            </a>
                        </div>
                    </div>

                    {{-- Narrow by Tag (Genre) --}}
                    <div class="mb-6">
                        <h3 class="font-bold text-white text-sm mb-3 uppercase tracking-wide border-b border-gray-600 pb-1">Narrow by Genre</h3>
                        <div class="space-y-1 text-sm text-[#556772]">
                            @foreach(['Action', 'RPG', 'Strategy', 'Adventure', 'Simulation', 'Horror', 'Indie', 'Racing'] as $genre)
                                <a href="{{ route('games.search', array_merge(request()->all(), ['genre' => $genre])) }}" 
                                   class="flex items-center gap-2 hover:text-white hover:bg-gray-700 p-1 rounded cursor-pointer transition {{ request('genre') == $genre ? 'text-[#7f5af0] bg-gray-800' : '' }}">
                                    <div class="w-4 h-4 border border-gray-500 rounded-sm flex items-center justify-center {{ request('genre') == $genre ? 'bg-purple-500 border-purple-500' : 'bg-[#101822]' }}">
                                        @if(request('genre') == $genre) <span class="text-white text-[10px]">✓</span> @endif
                                    </div>
                                    {{ $genre }}
                                </a>
                            @endforeach
                        </div>
                    </div>

                    @if(request()->has('q') || request()->has('genre') || request()->has('price'))
                        <a href="{{ route('games.search') }}" class="block text-center w-full bg-gray-700 hover:bg-purple-600 text-white text-xs py-2 rounded mt-4">
                            CLEAR ALL FILTERS
                        </a>
                    @endif

                </div>
            </div>

        </div>
    </div>
</div>
@endsection
@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-[#242629] py-12 text-white">
    <div class="max-w-3xl mx-auto px-4">
        
        <div class="bg-[#16161a] border-t-4 border-[#7f5af0] shadow-2xl p-8">
            <h1 class="text-3xl font-light text-white mb-8 uppercase tracking-wider">Edit Game: <span class="font-bold text-[#7f5af0]">{{ $game->title }}</span></h1>

            @if(session('success'))
                <div class="bg-green-600 text-white p-3 mb-6 rounded">{{ session('success') }}</div>
            @endif

            <form action="{{ route('games.update', $game) }}" method="POST" class="space-y-6">
                @csrf
                @method('PUT')

                {{-- Title --}}
                <div>
                    <label class="block text-[#66c0f4] font-bold text-sm uppercase mb-2">Game Title</label>
                    <input type="text" name="title" value="{{ $game->title }}" class="w-full bg-[#2a3f5a] text-white border border-[#000] p-3 focus:outline-none focus:border-[#66c0f4]">
                </div>

                {{-- Genre & Price --}}
                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <label class="block text-gray-400 font-bold text-sm uppercase mb-2">Genre</label>
                        <select name="genre" class="w-full bg-gray-500 text-white border border-[#000] p-3">
                            <option value="Action" {{ $game->genre == 'Action' ? 'selected' : '' }}>Action</option>
                            <option value="RPG" {{ $game->genre == 'RPG' ? 'selected' : '' }}>RPG</option>
                            <option value="Strategy" {{ $game->genre == 'Strategy' ? 'selected' : '' }}>Strategy</option>
                            <option value="Simulation" {{ $game->genre == 'Simulation' ? 'selected' : '' }}>Simulation</option>
                            <option value="Racing" {{ $game->genre == 'Racing' ? 'selected' : '' }}>Racing</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-gray-400 font-bold text-sm uppercase mb-2">Price (IDR)</label>
                        <input type="number" name="price" value="{{ $game->price }}" class="w-full bg-gray-500 text-white border border-[#000] p-3">
                    </div>
                </div>

                {{-- Cover Image URL --}}
                <div>
                    <label class="block text-gray-400 font-bold text-sm uppercase mb-2">Cover Image URL</label>
                    <input type="text" name="cover_image" value="{{ $game->cover_image }}" class="w-full bg-[#2a3f5a] text-white border border-[#000] p-3">
                    <p class="text-xs text-gray-500 mt-1">Direct link to image (JPG/PNG)</p>
                </div>

                {{-- Description --}}
                <div>
                    <label class="block text-gray-400 font-bold text-sm uppercase mb-2">Description</label>
                    <textarea name="description" rows="6" class="w-full bg-gray-500 text-white border border-[#000] p-3">{{ $game->description }}</textarea>
                </div>

                {{-- Action Buttons --}}
                <div class="flex justify-end gap-4 pt-6 border-t border-gray-700">
                    <a href="{{ route('game.show', $game) }}" class="text-gray-400 hover:text-white px-6 py-3 font-bold uppercase text-sm">Cancel</a>
                    <button type="submit" class="bg-[#7f5af0] hover:bg-[#7f5af0] text-black px-8 py-3 font-bold uppercase text-sm shadow-lg hover:translate-y-[-2px] transition">
                        Save Changes
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>
@endsection
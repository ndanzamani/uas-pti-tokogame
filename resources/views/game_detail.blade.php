@extends('layouts.guest')

@section('content')
    <div class="bg-[#16161a] text-gray-200 min-h-screen">

        @if(isset($game))
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

                {{-- JUDUL GAME --}}
                <h1 class="text-4xl font-bold text-white mb-2">{{ $game->title }}</h1>
                <p class="text-gray-400 mb-6 text-sm">Genre: {{ $game->genre }} | Publisher: {{ $game->publisher }}</p>

                <div class="flex space-x-8">

                    {{-- Kolom Kiri: Gambar Besar dan Tabs --}}
                    <div class="w-3/4">
                        {{-- Main Media Display --}}
                        <div class="mb-4 relative group">
                            <div id="media-container" class="w-full aspect-video bg-black rounded-lg shadow-xl overflow-hidden flex items-center justify-center border border-gray-800">
                                @if($game->trailer_url)
                                    <video id="main-video" controls class="w-full h-full object-contain" poster="{{ $game->cover_image }}">
                                        <source src="{{ $game->trailer_url }}" type="video/mp4">
                                        Your browser does not support the video tag.
                                    </video>
                                    <img id="main-image" src="{{ $game->cover_image }}" alt="{{ $game->title }}" class="w-full h-full object-contain hidden">
                                @else
                                    <img id="main-image" src="{{ $game->cover_image }}" alt="{{ $game->title }}" class="w-full h-full object-contain">
                                    <video id="main-video" controls class="w-full h-full object-contain hidden">
                                        <source src="" type="video/mp4">
                                    </video>
                                @endif
                            </div>
                        </div>

                        {{-- Thumbnails Carousel --}}
                        <div class="flex space-x-2 overflow-x-auto pb-2 scrollbar-hide mb-6">
                            {{-- Trailer Thumbnail (if exists) --}}
                            @if($game->trailer_url)
                                <div class="flex-shrink-0 w-28 h-16 cursor-pointer border-2 border-blue-500 rounded overflow-hidden relative media-thumb"
                                     onclick="showVideo('{{ $game->trailer_url }}')">
                                    <div class="absolute inset-0 bg-black/50 flex items-center justify-center">
                                        <svg class="w-8 h-8 text-white opacity-80" fill="currentColor" viewBox="0 0 20 20"><path d="M6.3 2.841A1.5 1.5 0 004 4.11V15.89a1.5 1.5 0 002.3 1.269l9.344-5.89a1.5 1.5 0 000-2.538L6.3 2.84z"/></svg>
                                    </div>
                                    <img src="{{ $game->cover_image }}" class="w-full h-full object-cover">
                                </div>
                            @endif

                            {{-- Cover Image Thumbnail --}}
                            <div class="flex-shrink-0 w-28 h-16 cursor-pointer border-2 border-transparent hover:border-white rounded overflow-hidden media-thumb"
                                 onclick="showImage('{{ $game->cover_image }}')">
                                <img src="{{ $game->cover_image }}" class="w-full h-full object-cover">
                            </div>

                            {{-- Screenshots Thumbnails --}}
                            @if($game->screenshots && is_array($game->screenshots))
                                @foreach($game->screenshots as $screenshot)
                                    <div class="flex-shrink-0 w-28 h-16 cursor-pointer border-2 border-transparent hover:border-white rounded overflow-hidden media-thumb"
                                         onclick="showImage('{{ $screenshot }}')">
                                        <img src="{{ $screenshot }}" class="w-full h-full object-cover">
                                    </div>
                                @endforeach
                            @endif
                        </div>

                        {{-- TAB BUTTONS --}}
                        <div class="mb-6">
                            <div class="flex border-b border-gray-700 mb-4">
                                <button id="tab-desc-btn"
                                    class="tab-btn p-3 px-6 text-sm font-semibold border-b-2 border-purple-500 text-white transition duration-150">
                                    DESKRIPSI
                                </button>
                                <button id="tab-specs-btn"
                                    class="tab-btn p-3 px-6 text-sm font-semibold border-b-2  border-purple-500 border-transparent text-gray-500 hover:text-white transition duration-150">
                                    SPESIFIKASI
                                </button>
                                <button id="tab-reviews-btn"
                                    class="tab-btn p-3 px-6 text-sm font-semibold border-b-2  border-purple-500 border-transparent text-gray-500 hover:text-white transition duration-150">
                                    ULASAN 
                                </button>
                            </div>

                            {{-- TAB CONTENT CONTAINERS --}}
                            <div id="tab-content">

                                {{-- KONTEN 1: DESKRIPSI (Default Active) --}}
                                <div id="content-desc" class="tab-page">
                                    <h2 class="text-2xl font-bold mb-3 text-white">Tentang Game Ini</h2>
                                    <p class="text-gray-400 leading-relaxed">{{ $game->description }}</p>
                                </div>

                                {{-- KONTEN 2: SPESIFIKASI (Hidden Awalnya) --}}
                                <div id="content-specs" class="tab-page hidden">
                                    <h2 class="text-2xl font-bold mb-3 text-white">Persyaratan Sistem</h2>
                                    <ul class="text-gray-400 space-y-2">
                                        <li><span class="font-bold text-white">OS:</span> Windows 10</li>
                                        <li><span class="font-bold text-white">Prosesor:</span> Intel Core i5 / AMD Ryzen 5</li>
                                        <li><span class="font-bold text-white">Memori:</span> 8 GB RAM</li>
                                        <li><span class="font-bold text-white">Penyimpanan:</span> 50 GB Tersedia</li>
                                    </ul>
                                </div>

                                {{-- KONTEN 3: ULASAN (Hidden Awalnya) --}}
                                <div id="content-reviews" class="tab-page hidden">
                                    <h2 class="text-2xl font-bold mb-3 text-white">Ulasan Pengguna</h2>
                                    <div class="p-4 bg-gray-800 rounded-md mb-3">
                                        <p class="font-semibold text-green-400">Sangat Positif</p>
                                        <p class="text-sm text-gray-400">"Game ini wajib dibeli! Ceritanya luar biasa." -
                                            GamerPro123</p>
                                    </div>
                                    <div class="p-4 bg-gray-800 rounded-md">
                                        <p class="font-semibold text-yellow-400">Campuran</p>
                                        <p class="text-sm text-gray-400">"Ada beberapa bug, tapi intinya seru." - CoderBebas</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Detail Tambahan --}}
                        <div class="grid grid-cols-2 gap-4 text-sm mt-8 border-t border-gray-700 pt-6">
                            <div>
                                <h3 class="font-bold text-white">Tanggal Rilis:</h3>
                                <p class="text-gray-400">{{ \Carbon\Carbon::parse($game->release_date)->format('d F Y') }}</p>
                            </div>
                            <div>
                                <h3 class="font-bold text-white">Publisher:</h3>
                                <p class="text-gray-400">{{ $game->publisher }}</p>
                            </div>
                        </div>

                    </div>

                    {{-- Kolom Kanan: Detail Beli --}}
                    <div class="w-1/4">
                        <div class="bg-[#242629] p-6 rounded-lg h-fit shadow-xl border border-gray-700">
                            <h3 class="text-xl font-bold mb-4 text-white">Beli {{ $game->title }}</h3>

                            {{-- Kalkulasi dan Kotak Harga --}}
                            @php
                                $finalPrice = $game->price;
                                $hasDiscount = $game->discount_percent ?? 0 > 0;

                                if ($hasDiscount) {
                                    $finalPrice = $game->price * (1 - ($game->discount_percent / 100));
                                    $originalPriceFormatted = 'Rp' . number_format($game->price, 0, ',', '.');
                                    $discountBadge = '<span class="bg-green-700 text-white font-bold p-1 text-sm rounded-sm mr-2">-' . $game->discount_percent . '%</span>';
                                } else {
                                    $originalPriceFormatted = '';
                                    $discountBadge = '';
                                }
                            @endphp

                            <div class="flex justify-between items-center bg-gray-700 p-3 rounded-md mb-4">
                                <div class="flex flex-col items-start">
                                    @if ($hasDiscount)
                                        <div class="flex items-center space-x-1">
                                            {!! $discountBadge !!}
                                            <span class="text-xs text-gray-500 line-through">{{ $originalPriceFormatted }}</span>
                                        </div>
                                        <span class="text-2xl font-black text-green-400">
                                            Rp{{ number_format($finalPrice, 0, ',', '.') }}
                                        </span>
                                    @else
                                        <span class="text-lg text-gray-400">Harga:</span>
                                        <span class="text-2xl font-black text-white">
                                            Rp{{ number_format($finalPrice, 0, ',', '.') }}
                                        </span>
                                    @endif
                                </div>
                            </div>

                            {{-- FORM UNTUK TOMBOL KERANJANG --}}
                            <form action="{{ route('cart.add', $game) }}" method="POST">
                                @csrf
                                <button type="submit"
                                    class="w-full text-center bg-[#2cb67d] text-white py-3 rounded-sm font-semibold text-lg">
                                    TAMBAH KE KERANJANG
                                </button>
                            </form>
                            {{-- END FORM KERANJANG --}}
                        </div>
                    </div>
                </div>

            </div>
        @else
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
                <h1 class="text-4xl font-bold text-red-500">Game Tidak Ditemukan (404)</h1>
                <a href="{{ route('store.index') }}" class="text-blue-400 hover:underline mt-4 block">Kembali ke Toko</a>
            </div>
        @endif
    </div>

    {{-- JAVASCRIPT UNTUK TAB INTERAKTIF --}}
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const tabButtons = document.querySelectorAll('.tab-btn');
                const tabPages = document.querySelectorAll('.tab-page');

                tabButtons.forEach(button => {
                    button.addEventListener('click', () => {
                        const targetId = button.id.replace('-btn', '');

                        // Nonaktifkan semua tombol dan konten
                        tabButtons.forEach(btn => {
                            btn.classList.remove('border-blue-500', 'text-white', 'font-semibold');
                            btn.classList.add('border-transparent', 'text-gray-500');
                        });

                        tabPages.forEach(page => {
                            page.classList.add('hidden');
                        });

                        // Aktifkan tombol yang diklik
                        button.classList.remove('border-transparent', 'text-gray-500');
                        button.classList.add('border-blue-500', 'text-white', 'font-semibold');

                        // Tampilkan konten yang sesuai
                        const targetContent = document.getElementById('content-' + targetId.substring(4)); // Ambil ID yang benar: content-desc
                        if (targetContent) {
                            targetContent.classList.remove('hidden');
                        }
                    });
                });
            });
            
            // Functions for Media Gallery
            function showImage(url) {
                const mainImage = document.getElementById('main-image');
                const mainVideo = document.getElementById('main-video');
                
                // Hide video, pause it
                mainVideo.classList.add('hidden');
                mainVideo.pause();
                
                // Show image
                mainImage.src = url;
                mainImage.classList.remove('hidden');
                
                // Active state styling for thumbnails (optional enhancement)
                document.querySelectorAll('.media-thumb').forEach(el => el.classList.remove('border-blue-500'));
                // event.target.closest('.media-thumb').classList.add('border-blue-500'); // Requires event passing
            }

            function showVideo(url) {
                const mainImage = document.getElementById('main-image');
                const mainVideo = document.getElementById('main-video');
                
                // Hide image
                mainImage.classList.add('hidden');
                
                // Show video
                mainVideo.src = url; // Update source if needed
                mainVideo.classList.remove('hidden');
                mainVideo.play();
                
                 // Active state styling for thumbnails
                 document.querySelectorAll('.media-thumb').forEach(el => el.classList.remove('border-blue-500'));
            }
        </script>
    @endpush

@endsection
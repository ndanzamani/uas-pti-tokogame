@extends('layouts.guest')

@section('content')
{{-- 1. LOAD SCRIPT MIDTRANS --}}
<script type="text/javascript"
        src="https://app.sandbox.midtrans.com/snap/snap.js"
        data-client-key="{{ config('midtrans.client_key') }}"></script>
{{-- Note: Ganti URL ke app.midtrans.com jika Production --}}

<div class="bg-[#242629] min-h-screen py-8">
    <div class="max-w-4xl mx-auto">
    
        {{-- Breadcrumb --}}
        <div class="text-xs text-[#7f5af0] font-bold mb-6 uppercase">
            <a href="{{ route('cart.index') }}" class="hover:text-white">Cart</a> > <span class="text-gray-400">Payment</span>
        </div>

        <h1 class="text-3xl font-light text-white uppercase tracking-wider mb-8">
            Payment <span class="font-bold text-[#7f5af0]">Method</span>
        </h1>
        
        @if(session('error'))
            <div class="bg-red-600 text-white p-4 rounded mb-6 border border-red-400 shadow-lg">
                {{ session('error') }}
            </div>
        @endif

        <div class="flex flex-col md:flex-row gap-8 bg-[#16161a] p-8 border border-black shadow-2xl">
            
            {{-- KIRI: Form Pembayaran --}}
            <div class="w-full md:w-2/3 border-r border-black pr-8">
                {{-- Tambahkan ID pada Form --}}
                <form id="checkoutForm" action="{{ route('cart.process') }}" method="POST">
                    @csrf
                    @if(isset($selectedVoucher))
                        <input type="hidden" name="voucher_id" value="{{ $selectedVoucher->id }}">
                    @endif
                    
                    {{-- Input Hidden untuk menyimpan hasil Midtrans --}}
                    <input type="hidden" name="midtrans_result" id="midtrans_result">
                    
                    {{-- Kita butuh passing total final ke JS --}}
                    <input type="hidden" id="final_total" value="{{ isset($finalTotal) ? $finalTotal : $total }}">

                    @php $kukusMoneyBalance = Auth::user()->kukus_money_balance ?? 0; @endphp
                    
                    <div class="mb-6">
                        <label class="block text-[#66c0f4] text-xs font-bold uppercase mb-2">Pilih Metode Pembayaran</label>
                        <div class="space-y-2">
                            
                            {{-- Opsi Kukus Money (Internal) --}}
                            <label class="flex flex-col bg-[#242629] p-3 rounded cursor-pointer border border-transparent hover:border-white group">
                                <div class="flex items-center">
                                    <input type="radio" name="payment_method" value="kukus_money" class="form-radio text-red-500 focus:ring-0">
                                    <span class="ml-3 text-white font-black uppercase text-base group-hover:text-red-400">Kukus Money</span>
                                    <span class="ml-auto text-sm text-gray-400">Digital Wallet</span>
                                </div>
                                <div class="mt-2 ml-7 text-xs text-red-300 font-bold">
                                    Saldo: Rp {{ number_format($kukusMoneyBalance, 0, ',', '.') }}
                                </div>
                            </label>

                            {{-- Metode Pihak Ketiga (Midtrans Group) --}}
                            {{-- Kita tambahkan class 'midtrans-method' untuk memudahkan seleksi di JS --}}
                            
                            <label class="flex items-center bg-[#242629] p-3 rounded cursor-pointer border border-transparent hover:border-white group">
                                <input type="radio" name="payment_method" value="gopay" class="form-radio midtrans-method text-[#66c0f4] focus:ring-0">
                                <span class="ml-3 text-white font-bold group-hover:text-[#66c0f4]">GoPay / QRIS</span>
                                <span class="ml-auto text-xs text-gray-400">E-Wallet</span>
                            </label>

                            <label class="flex items-center bg-[#242629] p-3 rounded cursor-pointer border border-transparent hover:border-white group">
                                <input type="radio" name="payment_method" value="bank_transfer" class="form-radio midtrans-method text-[#66c0f4] focus:ring-0">
                                <span class="ml-3 text-white font-bold group-hover:text-[#66c0f4]">Bank Transfer (VA)</span>
                                <span class="ml-auto text-xs text-gray-400">Virtual Account</span>
                            </label>

                            <label class="flex items-center bg-[#242629] p-3 rounded cursor-pointer border border-transparent hover:border-white group">
                                <input type="radio" name="payment_method" value="credit_card" class="form-radio midtrans-method text-[#66c0f4] focus:ring-0">
                                <span class="ml-3 text-white font-bold group-hover:text-[#66c0f4]">Visa / MasterCard</span>
                                <span class="ml-auto text-xs text-gray-400">Credit Card</span>
                            </label>
                        </div>
                        <span id="payment-error" class="text-red-500 text-xs mt-1 hidden">Silakan pilih metode pembayaran.</span>
                    </div>

                    <div class="mt-8 border-t border-gray-700 pt-6">
                        <p class="text-xs text-gray-400 mb-4">
                            Dengan mengklik "Lanjutkan Pembelian", Anda menyetujui <a href="#" class="text-white hover:underline">Perjanjian Pelanggan Kukus</a>.
                        </p>
                        
                        {{-- Ubah button type jadi button dulu (handle by JS), atau preventDefault di JS --}}
                        <button type="submit" id="pay-button" class="bg-[#2cb67d] hover:brightness-110 text-white font-bold py-3 px-8 rounded-sm shadow-lg uppercase tracking-wider text-sm w-full md:w-auto">
                            Lanjutkan Pembelian
                        </button>
                    </div>
                </form>
            </div>

            {{-- KANAN: Ringkasan (Tidak Berubah) --}}
            <div class="w-full md:w-1/3">
                <h3 class="text-gray-400 text-xs font-bold uppercase mb-4">Order Summary</h3>
                
                <div class="space-y-2 mb-4 max-h-60 overflow-y-auto custom-scrollbar">
                    @foreach($cart as $item)
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-300 truncate w-2/3">{{ $item['title'] }}</span>
                            <span class="text-gray-400">
                                @if(isset($item['discount_percent']) && $item['discount_percent'] > 0)
                                    Rp {{ number_format($item['price'] * (1 - $item['discount_percent'] / 100), 0, ',', '.') }}
                                @else
                                    Rp {{ number_format($item['price'], 0, ',', '.') }}
                                @endif
                            </span>
                        </div>
                    @endforeach
                </div>

                {{-- VOUCHER SECTION --}}
                <div class="mb-6 border-t border-gray-600 pt-4">
                    <form action="{{ route('cart.checkout') }}" method="GET" id="voucherForm">
                        <label class="block text-gray-400 text-xs font-bold uppercase mb-2">Apply Voucher</label>
                        <select name="voucher_id" onchange="document.getElementById('voucherForm').submit()" class="w-full bg-[#242629] text-white border-none rounded text-sm p-2">
                            <option value="">Select a voucher...</option>
                            @foreach($vouchers as $voucher)
                                <option value="{{ $voucher->id }}" {{ (isset($selectedVoucher) && $selectedVoucher->id == $voucher->id) ? 'selected' : '' }}>
                                    {{ $voucher->name }} 
                                    ({{ $voucher->type === 'percent' ? $voucher->discount_percent . '%' : 'Rp ' . number_format($voucher->discount_amount) }} Off)
                                </option>
                            @endforeach
                        </select>
                    </form>
                </div>

                <div class="border-t border-gray-600 pt-4 space-y-2">
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-gray-400">Subtotal:</span>
                        <span class="text-gray-300">Rp {{ number_format($total, 0, ',', '.') }}</span>
                    </div>
                    
                    @if(isset($discountAmount) && $discountAmount > 0)
                        <div class="flex justify-between items-center text-sm text-green-400">
                            <span>Discount ({{ $selectedVoucher->name }}):</span>
                            <span>- Rp {{ number_format($discountAmount, 0, ',', '.') }}</span>
                        </div>
                    @endif

                    <div class="flex justify-between items-center pt-2 border-t border-gray-700">
                        <span class="text-white font-bold">Total:</span>
                        <span class="text-[#7f5af0] font-black text-xl">
                            Rp {{ number_format(isset($finalTotal) ? $finalTotal : $total, 0, ',', '.') }}
                        </span>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

{{-- JAVASCRIPT INTEGRATION --}}
<script>
    document.getElementById('checkoutForm').addEventListener('submit', function(e) {
        let selectedPayment = document.querySelector('input[name="payment_method"]:checked');
        
        // 1. Validasi: Pastikan ada metode yang dipilih
        if (!selectedPayment) {
            e.preventDefault();
            document.getElementById('payment-error').classList.remove('hidden');
            return;
        }

        // 2. Logic Percabangan
        if (selectedPayment.value === 'kukus_money') {
            // Jika Kukus Money (Internal), biarkan form submit normal ke Laravel
            return true; 
        } else {
            // Jika Midtrans (DANA, QRIS, dll), cegah submit default
            e.preventDefault(); 
            
            let payButton = document.getElementById('pay-button');
            payButton.innerHTML = 'Loading...';
            payButton.disabled = true;

            // Panggil endpoint AJAX untuk minta Snap Token
            fetch("{{ route('cart.snap_token') }}", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": "{{ csrf_token() }}"
                },
                body: JSON.stringify({
                    total: document.getElementById('final_total').value,
                    payment_type: selectedPayment.value
                })
            })
            .then(response => response.json())
            .then(data => {
                if(data.error) {
                    alert('Error: ' + data.error);
                    payButton.disabled = false;
                    payButton.innerHTML = 'Lanjutkan Pembelian';
                    return;
                }

                // Buka Pop-up Snap
                window.snap.pay(data.snap_token, {
                    onSuccess: function(result) {
                        // Masukkan hasil JSON ke hidden input
                        document.getElementById('midtrans_result').value = JSON.stringify(result);
                        // Submit form secara manual ke 'cart.process' untuk simpan order di DB
                        document.getElementById('checkoutForm').submit();
                    },
                    onPending: function(result) {
                        document.getElementById('midtrans_result').value = JSON.stringify(result);
                        document.getElementById('checkoutForm').submit();
                    },
                    onError: function(result) {
                        alert("Pembayaran gagal!");
                        payButton.disabled = false;
                        payButton.innerHTML = 'Lanjutkan Pembelian';
                    },
                    onClose: function() {
                        alert('Anda menutup popup pembayaran.');
                        payButton.disabled = false;
                        payButton.innerHTML = 'Lanjutkan Pembelian';
                    }
                });
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan sistem.');
                payButton.disabled = false;
                payButton.innerHTML = 'Lanjutkan Pembelian';
            });
        }
    });
</script>
@endsection
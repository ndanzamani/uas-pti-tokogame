<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Midtrans\Config;
use Midtrans\Snap;
use App\Models\Order; // Pastikan Model Order sudah di-import!

class PaymentController extends Controller
{
    public function __construct()
    {
        // Konfigurasi Midtrans
        Config::$serverKey = config('midtrans.server_key');
        Config::$isProduction = config('midtrans.is_production');
        Config::$isSanitized = config('midtrans.is_sanitized');
        Config::$is3ds = config('midtrans.is_3ds');
    }

    // 1. Menampilkan Halaman Checkout
    public function checkout()
    {
        // 1. Ambil data Cart
        $cart = session('cart', []); 
        
        $total = 0;

        // 2. Hitung Total dengan Diskon
        foreach($cart as $item) {
            $price = $item['price'];
            if(isset($item['discount_percent']) && $item['discount_percent'] > 0) {
                $discountAmount = $price * ($item['discount_percent'] / 100);
                $finalItemPrice = $price - $discountAmount;
                $total += $finalItemPrice;
            } else {
                $total += $price;
            }
        }
        $total = (int) round($total);

        // --- DATA DUMMY (Jika cart kosong) ---
        if ($total == 0) {
            $cart = [
                ['title' => 'Black Myth: Wukong', 'price' => 244999, 'discount_percent' => 65]
            ];
            $total = 85750; 
        }

        // --- [BAGIAN INI YANG KURANG SEBELUMNYA] ---
        // Kita harus mendefinisikan $vouchers agar tidak error di blade.
        // Jika Anda belum punya tabel vouchers, kosongkan saja array-nya dulu.
        $vouchers = []; 
        
        // Atau jika ingin tes tampilan voucher, pakai dummy data ini (opsional):
        /*
        $vouchers = [
            (object)['id' => 1, 'name' => 'MERDEKA45', 'type' => 'percent', 'discount_percent' => 10, 'discount_amount' => 0],
            (object)['id' => 2, 'name' => 'HEMAT10RB', 'type' => 'fixed', 'discount_percent' => 0, 'discount_amount' => 10000],
        ];
        */

        // 3. Kirim variabel $vouchers ke View menggunakan compact
        return view('cart.checkout', compact('cart', 'total', 'vouchers'));
    }
    

    // 2. AJAX Handler: Generate Snap Token (Untuk tombol bayar Midtrans)
    public function getSnapToken(Request $request)
    {
        $user = Auth::user();

        // Validasi input
        $request->validate([
            'total' => 'required|numeric',
            'payment_type' => 'required'
        ]);

        // Buat Order ID Unik
        $orderId = 'TRX-' . time() . '-' . $user->id . '-' . rand(100, 999);

        // Parameter Midtrans
        $params = [
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => (int) $request->total,
            ],
            'customer_details' => [
                'first_name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone_number ?? '08123456789',
            ],
        ];

        try {
            $snapToken = Snap::getSnapToken($params);
            return response()->json([
                'snap_token' => $snapToken,
                'order_id' => $orderId
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // 3. PROCESS: Menangani Hasil Akhir (Logika yang Anda tanyakan)
    public function process(Request $request)
    {
        // Validasi dasar
        $request->validate([
            'payment_method' => 'required',
        ]);
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // A. LOGIC KUKUS MONEY (Saldo Internal)
        if ($request->payment_method === 'kukus_money') {
            
            // --- Contoh Logika Potong Saldo (Sesuaikan dengan nama kolom di DB Anda) ---
            $totalBayar = $request->input('final_total', 0); // Pastikan input ini ada di form atau ambil dari Cart session
            
            // Cek saldo cukup atau tidak
            if ($user->kukus_money_balance < $totalBayar) {
                return redirect()->back()->with('error', 'Saldo Kukus Money tidak mencukupi.');
            }

            // Kurangi Saldo
            $user->kukus_money_balance -= $totalBayar;
            $user->save();

            // Simpan Order Status PAID
            $order = Order::create([
                'order_id' => 'KUKUS-' . time() . '-' . $user->id,
                'user_id' => $user->id,
                'total_price' => $totalBayar,
                'status' => 'paid',
                'payment_type' => 'kukus_money',
            ]);

            return redirect()->route('home')->with('success', 'Pembayaran Berhasil via Kukus Money!');
        } 
        
        // B. LOGIC MIDTRANS
        else {
            // Ambil data hasil balik dari Snap JS (JSON string)
            $midtransData = json_decode($request->midtrans_result);
            
            // Pastikan data midtrans tidak kosong (jika user close popup sebelum selesai)
            if (!$midtransData) {
                return redirect()->back()->with('error', 'Transaksi dibatalkan atau data tidak valid.');
            }

            // Simpan Order ke Database
            $order = Order::create([
                'order_id' => $midtransData->order_id, // Gunakan ID dari Midtrans
                'user_id' => $user->id, // Gunakan variabel $user yang sudah didefinisikan di atas
                'total_price' => $midtransData->gross_amount,
                'status' => $midtransData->transaction_status == 'settlement' || $midtransData->transaction_status == 'capture' ? 'paid' : 'pending',
                'payment_type' => $midtransData->payment_type,
                'midtrans_json' => json_encode($midtransData), // Simpan log json lengkap buat debug
            ]);
            
            // Redirect ke halaman sukses (Sesuaikan route-nya)
            // return redirect()->route('payment.success', $order->id); 
            return redirect()->route('home')->with('success', 'Order Berhasil Dibuat!');
        }
    }

    // 4. Webhook Callback (Otomatis oleh Midtrans)
   public function callback(Request $request)
    {
        $serverKey = config('midtrans.server_key');
        $hashed = hash("sha512", $request->order_id.$request->status_code.$request->gross_amount.$serverKey);

        if ($hashed == $request->signature_key) {
            
            // Cek status sukses
            if ($request->transaction_status == 'capture' || $request->transaction_status == 'settlement') {
                
                // 1. Cari Order berdasarkan Order ID
                $order = Order::where('order_id', $request->order_id)->first();
                
                if ($order && $order->status !== 'paid') {
                    // Update status order jadi PAID
                    $order->update(['status' => 'paid']);

                    // 2. LOGIKA KHUSUS TOPUP
                    // Cek apakah Order ID diawali dengan "TOPUP-"
                    if (str_contains($request->order_id, 'TOPUP-')) {
                        // Ambil User pemilik order
                        $user = \App\Models\User::find($order->user_id);
                        
                        if ($user) {
                            // Tambah saldo user
                            $user->kukus_money_balance += $order->total_price;
                            $user->save();
                        }
                    }
                }
            }
        }
        return response()->json(['status' => 'success']);
    }
}
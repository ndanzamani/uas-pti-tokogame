<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


class TopupController extends Controller
{
    // Menampilkan halaman topup
    public function create()
    {
        // Pilihan jumlah top-up Kukus Money (IDR)
        $amounts = [
            50000, 100000, 200000, 500000, 1000000,
        ];

        return view('pages.topup', compact('amounts'));
    }

    // Memproses topup Kukus Money
    public function store(Request $request)
    {
        // 1. Ambil nilai: Prioritaskan input manual, jika kosong baru ambil preset
        // Kita menggunakan helper 'filled' untuk mengecek apakah input tidak kosong
        $amount = $request->filled('manual_amount') 
                    ? $request->manual_amount 
                    : $request->preset_amount;

        // 2. Masukkan nilai yang sudah dipilih kembali ke request untuk divalidasi
        $request->merge(['final_amount' => $amount]);

        // 3. Validasi 'final_amount'
        $request->validate([
            'final_amount' => 'required|numeric|min:10000|max:10000000', 
        ], [
            'final_amount.required' => 'Silakan pilih nominal atau masukkan jumlah manual.',
            'final_amount.min' => 'Minimal topup adalah Rp 10.000',
        ]);

        $user = Auth::user();
        
        // Pastikan amount di-cast ke float/integer agar operasi matematika aman
        $topupAmount = (float) $request->final_amount;

        // Simulasi proses pembayaran sukses, tambahkan saldo
        // Pastikan kukus_money_balance memiliki default value 0 di database jika null
        $currentBalance = $user->kukus_money_balance ?? 0;
        $user->kukus_money_balance = $currentBalance + $topupAmount;
        
        $user->save();

        return redirect()->back()->with('success', 
            "Topup sebesar Rp" . number_format($topupAmount, 0, ',', '.') . 
            " berhasil! Saldo Kukus Money Anda sekarang: Rp" . number_format($user->kukus_money_balance, 0, ',', '.')
        );
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    // Izinkan kolom-kolom ini diisi secara massal
    protected $fillable = [
        'order_id',
        'user_id',
        'total_price',
        'status',
        'payment_type',
        'midtrans_json',
    ];

    // Relasi ke User (opsional, berguna nanti buat history belanja)
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
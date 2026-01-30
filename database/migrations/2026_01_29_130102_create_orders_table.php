<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            // order_id string karena format kita 'TRX-...'
            $table->string('order_id')->unique(); 
            
            // Menyimpan ID user yang beli
            $table->unsignedBigInteger('user_id');
            
            // Total harga (decimal biar aman, atau bigInteger)
            $table->decimal('total_price', 15, 2);
            
            // Status: pending, paid, failed, expired
            $table->string('status')->default('pending');
            
            // Tipe pembayaran: gopay, bank_transfer, kukus_money, dll
            $table->string('payment_type')->nullable();
            
            // Simpan respon lengkap JSON dari Midtrans (untuk debug)
            $table->text('midtrans_json')->nullable(); 
            
            $table->timestamps();

            // Relasi (Opsional, biar aman datanya)
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
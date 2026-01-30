<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\LibraryController; 
use App\Http\Controllers\PageController;
use App\Http\Controllers\RefundController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\DailyChallengeController;
use App\Http\Controllers\VoucherController;
use App\Http\Controllers\TopupController;
use App\Http\Controllers\PaymentController; 
use Illuminate\Support\Facades\Route;

// --- EXISTING ROUTES ---
Route::get('/', [GameController::class, 'index'])->name('store.index');
Route::get('/game/{game}', [GameController::class, 'show'])->name('game.show'); // Gunakan {game} agar sesuai dengan parameter di controller
Route::get('/search', [GameController::class, 'search'])->name('games.search');

// --- STATIC PAGE ROUTES ---
Route::get('/about', [PageController::class, 'about'])->name('pages.about');
Route::get('/privacy', [PageController::class, 'privacy'])->name('pages.privacy');
Route::get('/terms', [PageController::class, 'terms'])->name('pages.terms');
Route::get('/stats', [PageController::class, 'stats'])->name('pages.stats');

// --- CART ROUTES ---
Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart/add/{game}', [CartController::class, 'addToCart'])->name('cart.add');
Route::delete('/cart/remove/{id}', [CartController::class, 'remove'])->name('cart.remove');
Route::get('/checkout', [CartController::class, 'checkout'])->name('cart.checkout');
Route::post('/checkout/process', [CartController::class, 'processPayment'])->name('cart.process');
Route::get('/checkout/success', [CartController::class, 'success'])->name('cart.success');
Route::get('/checkout/receipt', [CartController::class, 'downloadReceipt'])->name('cart.receipt');

// --- AUTH ROUTES ---
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    // --- KUKUS MONEY TOPUP ROUTES (BARU) ---
    Route::get('/kukus-money/topup', [TopupController::class, 'create'])->name('kukus.topup.create');
    Route::post('/kukus-money/topup', [TopupController::class, 'store'])->name('kukus.topup.store');
    // --- END KUKUS MONEY ---

    Route::get('/library', [LibraryController::class, 'index'])->name('library.index');
    Route::get('/library/download/{game}', [LibraryController::class, 'download'])->name('library.download');
    Route::post('/shelf', [LibraryController::class, 'storeShelf'])->name('shelf.store');

    // Profile Routes
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    // Publisher Request
    Route::post('/request-publisher', function (Illuminate\Http\Request $request) {
        $user = $request->user();
        $user->publisher_request_status = 'pending';
        $user->save();
        return back()->with('status', 'request-sent');
    })->name('user.request_publisher');

    // Route khusus Publisher untuk Upload, Edit, Delete, dan Tracking Game
    Route::middleware('role:publisher')->group(function() {
        
        // --- FITUR BARU: Publisher Dashboard untuk Tracking ---
        Route::get('/publisher/dashboard', [GameController::class, 'publisherDashboard'])->name('publisher.dashboard');

        // Create
        Route::get('/publish/game', [GameController::class, 'create'])->name('games.create');
        Route::post('/publish/game', [GameController::class, 'store'])->name('games.store');
        
        // Edit & Update (Publisher hanya bisa edit game miliknya sendiri - Logic otorisasi ada di Controller)
        Route::get('/games/{game}/edit', [GameController::class, 'edit'])->name('games.edit');
        Route::put('/games/{game}', [GameController::class, 'update'])->name('games.update');
        
        // Delete Game (Soft delete/hide) - Publisher hanya boleh delete game miliknya
        Route::delete('/games/{game}', [GameController::class, 'destroy'])->name('games.destroy');

    });

    // --- REFUND ROUTES ---
    Route::get('/support/refunds', [RefundController::class, 'create'])->name('refunds.create');
    Route::post('/support/refunds', [RefundController::class, 'store'])->name('refunds.store');
});

// --- ADMIN ROUTES (Hanya untuk Admin) ---
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/admin', [AdminController::class, 'index'])->name('admin.dashboard');
    Route::get('/admin/history', [AdminController::class, 'history'])->name('admin.history');
    
    // Publisher Actions
    Route::post('/admin/approve/{user}', [AdminController::class, 'approvePublisher'])->name('admin.approvePublisher');
    Route::post('/admin/reject/{user}', [AdminController::class, 'rejectPublisher'])->name('admin.rejectPublisher');
    
    // Game Approval Actions
    Route::post('/admin/game/{game}/approve', [AdminController::class, 'approveGame'])->name('admin.game.approve');
    Route::post('/admin/game/{game}/reject', [AdminController::class, 'rejectGame'])->name('admin.game.reject');

    // Refund Actions
    Route::post('/admin/refund/{id}/approve', [AdminController::class, 'approveRefund'])->name('admin.refund.approve');
    Route::post('/admin/refund/{id}/reject', [AdminController::class, 'rejectRefund'])->name('admin.refund.reject');
    
    // Game Management (Delete permanen oleh Admin)
    Route::delete('/admin/games/{game}', [GameController::class, 'destroy'])->name('admin.games.destroy');
    
    // --- ADMIN TEST FEATURES ---
    Route::post('/admin/daily-challenge/set-game', [DailyChallengeController::class, 'setGame'])->name('admin.daily.setGame');
    Route::post('/admin/voucher/grant/{voucher}', [VoucherController::class, 'grant'])->name('admin.voucher.grant');
});

// --- DAILY CHALLENGE & VOUCHERS (Available for all authenticated users) ---
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/daily-challenge', [DailyChallengeController::class, 'index'])->name('daily.challenge');
    Route::post('/daily-challenge/complete', [DailyChallengeController::class, 'complete'])->name('daily.complete');
    
    Route::get('/voucher-shop', [VoucherController::class, 'index'])->name('voucher.shop');
    Route::post('/voucher-shop/buy/{voucher}', [VoucherController::class, 'buy'])->name('voucher.buy');
});

// payment
Route::get('/checkout', [PaymentController::class, 'checkout']);
Route::post('/midtrans/callback', [PaymentController::class, 'callback']);


// Route untuk menampilkan halaman checkout
Route::get('/checkout', [PaymentController::class, 'checkout'])->name('cart.checkout');

// Route AJAX untuk mengambil Snap Token (PENTING)
Route::post('/cart/snap-token', [PaymentController::class, 'getSnapToken'])->name('cart.snap_token');

// Route Callback Midtrans (Non-CSRF)
Route::post('/midtrans/callback', [PaymentController::class, 'callback']);

require __DIR__.'/auth.php';
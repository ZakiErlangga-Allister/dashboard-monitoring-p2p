<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\JadwalBayarController;
use App\Http\Controllers\CatatanDokumenController;
use App\Http\Controllers\LacakStatusController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Admin\LogAktivitasController;
use App\Http\Controllers\Admin\PengaturanController;
use App\Http\Controllers\Admin\PenggunaController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'peran:pengguna_umum,staf_keuangan,admin'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::middleware(['auth', 'peran:staf_keuangan,admin'])->get('/uji-keuangan', fn () => 'Halaman Keuangan');

Route::middleware(['auth', 'peran:staf_keuangan,admin'])->group(function () {
    Route::get('/jadwal-bayar', [JadwalBayarController::class, 'index'])->name('jadwal-bayar.index');
    Route::get('/jadwal-bayar/{invoice}', [CatatanDokumenController::class, 'show'])->name('jadwal-bayar.show');
});

Route::middleware(['auth', 'peran:staf_keuangan'])->group(function () {
    Route::put('/jadwal-bayar/{invoice}/catatan', [CatatanDokumenController::class, 'update'])
        ->name('jadwal-bayar.catatan.update');
});

Route::middleware(['auth', 'peran:pengguna_umum,staf_keuangan,admin'])->group(function () {
    Route::get('/lacak-status', [LacakStatusController::class, 'index'])->name('lacak-status.index');
    Route::get('/lacak-status/{purchaseRequisition}', [LacakStatusController::class, 'show'])->name('lacak-status.show');
});

Route::middleware(['auth', 'peran:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/log', [LogAktivitasController::class, 'index'])->name('log.index');

    Route::get('/pengaturan', [PengaturanController::class, 'edit'])->name('pengaturan.edit');
    Route::put('/pengaturan', [PengaturanController::class, 'update'])->name('pengaturan.update');

    Route::get('/pengguna', [PenggunaController::class, 'index'])->name('pengguna.index');
    Route::post('/pengguna', [PenggunaController::class, 'store'])->name('pengguna.store');
    Route::patch('/pengguna/{user}', [PenggunaController::class, 'update'])->name('pengguna.update');
});
});

require __DIR__.'/auth.php';

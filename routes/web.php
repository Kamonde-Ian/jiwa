<?php

use App\Livewire\Dashboard;
use App\Livewire\Deposits;
use App\Livewire\Invest;
use App\Livewire\Investments;
use App\Livewire\Kyc;
use App\Livewire\Referrals;
use App\Livewire\Security;
use App\Livewire\Statements;
use App\Livewire\Wallets;
use App\Livewire\Withdrawals;
use App\Http\Controllers\MarketingController;
use Illuminate\Support\Facades\Route;

/* Health check for Render */
Route::get('/up', fn () => response()->json(['status' => 'ok']));

/* Public marketing pages */
Route::view('/', 'marketing.home')->name('home');
Route::view('/about', 'marketing.about')->name('public.about');
Route::view('/plans', 'marketing.plans')->name('public.plans');
Route::view('/faq', 'marketing.faq')->name('public.faq');
Route::get('/contact', [MarketingController::class, 'contact'])->name('public.contact');
Route::post('/contact', [MarketingController::class, 'store'])->name('public.contact.store');
Route::view('/terms', 'marketing.terms')->name('public.terms');
Route::view('/privacy', 'marketing.privacy')->name('public.privacy');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', Dashboard::class)->name('dashboard');

    Route::get('/wallets', Wallets::class)->name('wallets');
    Route::get('/invest', Invest::class)->name('invest');
    Route::get('/investments', Investments::class)->name('investments.index');
    Route::get('/deposits', Deposits::class)->name('deposits.index');
    Route::get('/withdrawals', Withdrawals::class)->name('withdrawals.index');
    Route::get('/referrals', Referrals::class)->name('referrals');
    Route::get('/security', Security::class)->name('security');
    Route::get('/statements', Statements::class)->name('statements.index');

    Route::get('profile', Kyc::class)->name('profile');
});

require __DIR__.'/auth.php';

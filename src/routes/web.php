<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\LoginController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\StripeController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', [ItemController::class, 'index'])->name('index');
Route::get('/item/{item_id}', [ItemController::class, 'show'])->name('detail');
Route::post('/login', [LoginController::class, 'login'])->name('login');
Route::get('/register', [LoginController::class, 'showRegisterForm'])->name('register.form');
Route::post('/register', [LoginController::class, 'register'])->name('register');

// 認証済みユーザーのみアクセス可能なルート
Route::middleware(['auth'])->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    Route::post('/cart/add', [ItemController::class, 'add'])->name('cart.add');
    Route::get('/cart', [ItemController::class, 'index'])->name('cart.index');
    Route::get('/address', [UserController::class, 'address']);
    Route::get('/mypage', [UserController::class, 'profile'])->name('mypage');
    Route::post('/profile/update', [UserController::class, 'updateProfile'])->name('profile.update');
    Route::put('/address/update', [UserController::class, 'updateAddress'])->name('address.update');
    Route::get('/purchase/{exhibition_id}', [ItemController::class, 'purchases'])->name('purchase');
    Route::post('/purchase/complete', [ItemController::class, 'complete'])->name('purchase.complete');
    Route::get('/sell', [ItemController::class, 'create'])->name('sell');
    Route::post('/products', [ItemController::class, 'store'])->name('sell.store');
    Route::post('/comments', [ItemController::class, 'storeComment'])->name('comments.store');
    Route::post('/favorites/toggle', [ItemController::class, 'toggle'])->name('favorites.toggle');
    Route::get('/profile/edit', [UserController::class, 'editProfile'])->name('profile.edit');
    Route::put('/profile/update', [UserController::class, 'updateProfile'])->name('profile.update');
    Route::get('/exhibition/create', [ItemController::class, 'create'])->name('exhibition.create');
    Route::post('/exhibition/store', [ItemController::class, 'store'])->name('exhibition.store');
});

// 認証ページ（表示用）
Route::get('/email/verify', [LoginController::class, 'showVerificationNotice'])
    ->middleware('auth')
    ->name('verification.notice');

// 認証リンククリック後（自動で処理）
Route::get('/email/verify/{id}/{hash}', [LoginController::class, 'verify'])
    ->middleware(['auth', 'signed'])
    ->name('verification.verify');

// 再送信処理
Route::post('/email/verification-notification', [LoginController::class, 'resendVerificationEmail'])
    ->middleware(['auth', 'throttle:6,1'])
    ->name('verification.send');

Route::get('/purchase/address/{item_id}', [UserController::class, 'purchaseAddress'])->middleware(['auth', 'verified'])->name('purchase.address');

Route::get('/search', [SearchController::class, 'search'])->name('search');
Route::post('/store-tab', [SearchController::class, 'storeTab'])->name('store.tab');

Route::post('/stripe/create-checkout-session', [StripeController::class, 'createCheckoutSession'])->name('stripe.checkout');
Route::get('/purchase/success', [StripeController::class, 'success'])->name('purchase.success');
Route::get('/purchase/cancel', [StripeController::class, 'cancel'])->name('purchase.cancel');

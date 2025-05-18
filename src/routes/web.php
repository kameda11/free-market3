<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\LoginController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use App\Http\Controllers\SearchController;

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
});

// 認証ページ（表示用）
Route::get('/email/verify', function () {
    return view('auth.verify-email');
})->middleware('auth')->name('verification.notice');

// 認証リンククリック後（自動で処理）
Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill();
    return redirect()->route('profile.edit')->with('success', 'メール認証が完了しました！');
})->middleware(['auth', 'signed'])->name('verification.verify');

// 再送信処理
Route::post('/email/verification-notification', function (Request $request) {
    $request->user()->sendEmailVerificationNotification();
    return back()->with('message', '認証メールを再送しました。');
})->middleware(['auth', 'throttle:6,1'])->name('verification.send');

//Route::get('/purchase/address/{item_id}', [UserController::class, 'address'])->middleware('verified')->name('address');
Route::get('/purchase/address/{item_id}', [UserController::class, 'purchaseAddress'])->middleware(['auth', 'verified'])->name('purchase.address');

Route::get('/search', [SearchController::class, 'search'])->name('search');
Route::post('/store-tab', [SearchController::class, 'storeTab'])->name('store.tab');

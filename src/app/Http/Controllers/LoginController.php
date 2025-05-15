<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Auth\Events\Registered;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    public function login(Request $request)
    {
        // バリデーション
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // ログイン試行
        if (Auth::attempt($request->only('email', 'password'), $request->filled('remember'))) {
            $user = Auth::user();

            // メール認証が完了していない場合
            if (!$user->email_verified_at) {
                Auth::logout();
                return back()->withErrors([
                    'email' => 'メール認証が完了していません。登録したメールアドレスに送信された認証メールをご確認ください。',
                ]);
            }

            // ログイン後、index.blade.phpにリダイレクト
            return redirect()->route('index');  // 'index' はweb.phpで定義した名前付きルート
        }

        return back()->withErrors([
            'email' => 'メールアドレスまたはパスワードが正しくありません。',
        ]);
    }

    public function showRegisterForm()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        // バリデーション
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);
        // ユーザー作成
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        event(new Registered($user)); // 認証メール送信
        Auth::login($user); // 自動ログイン

        return redirect()->route('verification.notice'); // 認証ページへリダイレクト
    }
}

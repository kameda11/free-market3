<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Auth\Events\Registered;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Auth\EmailVerificationRequest;

class LoginController extends Controller
{
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

        // ユーザーをログイン状態にする
        Auth::login($user);

        // Registeredイベントを発火（認証メール送信）
        event(new Registered($user));

        return redirect()->route('verification.notice');
    }

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
            return redirect()->route('index');
        }

        return back()->withErrors([
            'email' => 'ログイン情報が登録されていません',
        ]);
    }

    /**
     * ログアウト処理
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('index');
    }

    /**
     * メール認証ページを表示
     */
    public function showVerificationNotice()
    {
        return view('auth.verify-email');
    }

    /**
     * メール認証を実行
     */
    public function verify(EmailVerificationRequest $request)
    {
        $request->fulfill();
        return redirect()->route('profile.edit')->with('success', 'メール認証が完了しました！');
    }

    /**
     * 認証メールを再送信
     */
    public function resendVerificationEmail(Request $request)
    {
        $request->user()->sendEmailVerificationNotification();
        return back()->with('status', '認証メールを再送信しました。');
    }
}

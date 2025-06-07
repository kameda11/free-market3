<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class LogoutTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ログアウトが成功する場合のテスト
     *
     * @return void
     */
    public function test_successful_logout()
    {
        // テスト用のユーザーを作成
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now()
        ]);

        // ユーザーとしてログイン
        Auth::login($user);

        // ログイン状態を確認
        $this->assertAuthenticated();

        // ログアウトを実行
        $response = $this->post('/logout');

        // リダイレクトを確認
        $response->assertRedirect('/');

        // 認証されていないことを確認
        $this->assertGuest();
    }
}

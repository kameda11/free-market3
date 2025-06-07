<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;

class LoginFormTest extends TestCase
{
    use RefreshDatabase;

    /**
     * メールアドレスが未入力の場合のバリデーションテスト
     *
     * @return void
     */
    public function test_email_validation()
    {
        $response = $this->post('/login', [
            'password' => 'password123'
        ]);

        $response->assertSessionHasErrors('email');
        $response->assertSessionHasErrorsIn('default', ['email' => 'メールアドレスを入力してください']);
    }

    /**
     * パスワードが未入力の場合のバリデーションテスト
     *
     * @return void
     */
    public function test_password_validation()
    {
        $response = $this->post('/login', [
            'email' => 'test@example.com'
        ]);

        $response->assertSessionHasErrors('password');
        $response->assertSessionHasErrorsIn('default', ['password' => 'パスワードを入力してください']);
    }

    /**
     * 登録されていない情報でログインを試みる場合のテスト
     *
     * @return void
     */
    public function test_unregistered_credentials()
    {
        $response = $this->post('/login', [
            'email' => 'unregistered@example.com',
            'password' => 'password123'
        ]);

        $response->assertSessionHasErrors('email');
        $response->assertSessionHasErrorsIn('default', ['email' => 'ログイン情報が登録されていません']);
    }

    /**
     * ログインが成功する場合のテスト
     *
     * @return void
     */
    public function test_successful_login()
    {
        // テスト用のユーザーを作成
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now() // メール認証済み
        ]);

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);

        $response->assertRedirect('/');
        $this->assertAuthenticated();
    }
}

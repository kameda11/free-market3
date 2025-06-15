<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;

class LoginFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_validation()
    {
        $response = $this->post('/login', [
            'password' => 'password123'
        ]);

        $response->assertSessionHasErrors('email');
        $response->assertSessionHasErrorsIn('default', ['email' => 'メールアドレスを入力してください']);
    }

    public function test_password_validation()
    {
        $response = $this->post('/login', [
            'email' => 'test@example.com'
        ]);

        $response->assertSessionHasErrors('password');
        $response->assertSessionHasErrorsIn('default', ['password' => 'パスワードを入力してください']);
    }

    public function test_unregistered_credentials()
    {
        $response = $this->post('/login', [
            'email' => 'unregistered@example.com',
            'password' => 'password123'
        ]);

        $response->assertSessionHasErrors('email');
        $response->assertSessionHasErrorsIn('default', ['email' => 'ログイン情報が登録されていません']);
    }

    public function test_successful_login()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now()
        ]);

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);

        $response->assertRedirect('/');
        $this->assertAuthenticated();
    }
}

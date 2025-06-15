<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class RegisterFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_name_validation()
    {
        $response = $this->post('/register', [
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'postal_code' => '123-4567',
            'address' => 'テスト住所',
            'phone' => '090-1234-5678'
        ]);

        $response->assertSessionHasErrors('name');
        $response->assertSessionHasErrorsIn('default', ['name' => 'お名前を入力してください']);
    }

    /**
     * メールアドレスが未入力の場合のバリデーションテスト
     *
     * @return void
     */
    public function test_email_validation()
    {
        $response = $this->post('/register', [
            'name' => 'テスト太郎',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'postal_code' => '123-4567',
            'address' => 'テスト住所',
            'phone' => '090-1234-5678'
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
        $response = $this->post('/register', [
            'name' => 'テスト太郎',
            'email' => 'test@example.com',
            'password_confirmation' => 'password123',
            'postal_code' => '123-4567',
            'address' => 'テスト住所',
            'phone' => '090-1234-5678'
        ]);

        $response->assertSessionHasErrors('password');
        $response->assertSessionHasErrorsIn('default', ['password' => 'パスワードを入力してください']);
    }

    /**
     * パスワードが7文字以下の場合のバリデーションテスト
     *
     * @return void
     */
    public function test_password_length_validation()
    {
        $response = $this->post('/register', [
            'name' => 'テスト太郎',
            'email' => 'test@example.com',
            'password' => 'pass123',
            'password_confirmation' => 'pass123',
            'postal_code' => '123-4567',
            'address' => 'テスト住所',
            'phone' => '090-1234-5678'
        ]);

        $response->assertSessionHasErrors('password');
        $response->assertSessionHasErrorsIn('default', ['password' => 'パスワードは8文字以上で入力してください']);
    }

    /**
     * パスワードとパスワード確認が一致しない場合のバリデーションテスト
     *
     * @return void
     */
    public function test_password_confirmation_validation()
    {
        $response = $this->post('/register', [
            'name' => 'テスト太郎',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password456',
            'postal_code' => '123-4567',
            'address' => 'テスト住所',
            'phone' => '090-1234-5678'
        ]);

        $response->assertSessionHasErrors('password');
        $response->assertSessionHasErrorsIn('default', ['password' => 'パスワードと一致しません']);
    }
}

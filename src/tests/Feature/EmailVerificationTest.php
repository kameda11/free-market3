<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Event;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    /**
     * 会員登録とメール認証のテスト
     * 1. 会員登録をする
     * 2. 認証メールを送信する
     * 期待挙動: 登録したメールアドレス宛に認証メールが送信されている
     *
     * @return void
     */
    public function test_registration_and_email_verification()
    {
        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->post('/register', $userData);

        $response->assertRedirect('/email/verify');
        $this->assertAuthenticated();

        /** @phpstan-ignore-next-line */
        $user = User::where('email', 'test@example.com')->first();
        $this->assertNotNull($user);
        $this->assertFalse($user->hasVerifiedEmail());

        Notification::assertSentTo(
            $user,
            \App\Notifications\VerifyEmail::class
        );
    }

    /**
     * メール認証導線のテスト
     * 1. メール認証導線画面を表示する
     * 2. 「認証はこちらから」ボタンを押下
     * 3. メール認証サイトを表示する
     * 期待挙動: メール認証サイトに遷移する
     *
     * @return void
     */
    public function test_email_verification_flow()
    {
        /** @phpstan-ignore-next-line */
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $this->actingAs($user);

        $response = $this->get('/email/verify');
        $response->assertStatus(200)
            ->assertViewIs('auth.verify-email');

        // 認証メールの再送信をリクエスト
        $response = $this->post('/email/verification-notification');
        $response->assertRedirect()
            ->assertSessionHas('status', '認証メールを再送信しました。');

        Notification::assertSentTo(
            $user,
            \App\Notifications\VerifyEmail::class
        );

        Event::fake();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $response = $this->get($verificationUrl);
        $response->assertRedirect('/profile/edit')
            ->assertSessionHas('success', 'メール認証が完了しました！');

        Event::assertDispatched(Verified::class);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    /**
     * メール認証完了後の商品一覧画面遷移のテスト
     * 1. メール認証を完了する
     * 2. 商品一覧画面を表示する
     * 期待挙動: 商品一覧画面に遷移する
     *
     * @return void
     */
    public function test_verification_completion_and_item_list_redirect()
    {
        // 未認証ユーザーを作成
        /** @var Authenticatable $user */
        $user = User::factory()->create([
            'email_verified_at' => null,
            'password' => Hash::make('password123')
        ]);

        // 1. メール認証を完了
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $response = $this->actingAs($user)->get($verificationUrl);

        // 認証完了の確認
        $response->assertRedirect(route('profile.edit'))
            ->assertSessionHas('success', 'メール認証が完了しました！');

        // ユーザーの認証状態を確認
        $user = User::find($user->id);
        $this->assertNotNull($user->email_verified_at);

        // 2. 商品一覧画面を表示
        $response = $this->actingAs($user)->get(route('index'));

        // 商品一覧画面への遷移を確認
        $response->assertStatus(200)
            ->assertViewIs('index')
            ->assertSee('おすすめ')
            ->assertSee('マイリスト');
    }
}

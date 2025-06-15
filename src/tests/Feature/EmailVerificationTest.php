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

        /** @var Authenticatable|User $user */
        $user = User::where('email', 'test@example.com')->first();
        $this->assertNotNull($user);
        $this->assertFalse($user->hasVerifiedEmail());

        Notification::assertSentTo(
            $user,
            \App\Notifications\VerifyEmail::class
        );
    }

    public function test_email_verification_flow()
    {
        /** @var Authenticatable|User $user */
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $this->actingAs($user);

        $response = $this->get('/email/verify');
        $response->assertStatus(200)
            ->assertViewIs('auth.verify-email');

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

    public function test_verification_completion_and_item_list_redirect()
    {
        /** @var Authenticatable|User $user */
        $user = User::factory()->create([
            'email_verified_at' => null,
            'password' => Hash::make('password123')
        ]);

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $response = $this->actingAs($user)->get($verificationUrl);

        $response->assertRedirect(route('profile.edit'))
            ->assertSessionHas('success', 'メール認証が完了しました！');

        $user = User::find($user->id);
        $this->assertNotNull($user->email_verified_at);

        $response = $this->actingAs($user)->get(route('index'));

        $response->assertStatus(200)
            ->assertViewIs('index')
            ->assertSee('おすすめ')
            ->assertSee('マイリスト');
    }
}

<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Address;
use Illuminate\Contracts\Auth\Authenticatable;

class UserProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    /**
     * プロフィールページ初期表示テスト
     * 1. ユーザーにログインする
     * 2. プロフィールページを開く
     * 期待挙動: 各項目の初期値が正しく表示されている
     *
     * @return void
     */
    public function test_profile_page_initial_display()
    {
        // テスト用のユーザーを作成
        /** @var Authenticatable $user */
        $user = User::factory()->create([
            'name' => '山田太郎',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now()
        ]);

        // 住所情報を登録
        $address = Address::create([
            'user_id' => $user->id,
            'name' => '山田太郎',
            'post_code' => '123-4567',
            'address' => '東京都渋谷区渋谷1-1-1',
            'building' => 'マンション101'
        ]);

        // 1. ユーザーにログイン
        $this->actingAs($user);

        // 2. プロフィールページを開く
        $response = $this->get(route('profile.edit'));

        // レスポンスの確認
        $response->assertStatus(200)
            // プロフィール情報の確認
            ->assertSee('山田太郎')  // ユーザー名
            ->assertSee('123-4567')  // 郵便番号
            ->assertSee('東京都渋谷区渋谷1-1-1')  // 住所
            ->assertSee('マンション101')  // 建物名
            // フォームの初期値確認
            ->assertSee('value="山田太郎"', false)  // 名前の初期値
            ->assertSee('value="123-4567"', false)  // 郵便番号の初期値
            ->assertSee('value="東京都渋谷区渋谷1-1-1"', false)  // 住所の初期値
            ->assertSee('value="マンション101"', false);  // 建物名の初期値
    }
}

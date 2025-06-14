<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Address;
use App\Models\Profile;
use App\Models\Exhibition;
use App\Models\Purchase;
use Illuminate\Contracts\Auth\Authenticatable;

class UserTest extends TestCase
{
    use RefreshDatabase;

    /**
     * プロフィールページの表示テスト
     * 1. ユーザーにログインする
     * 2. プロフィールページを開く
     * 期待挙動: プロフィール画像、ユーザー名、出品した商品一覧、購入した商品一覧が正しく表示される
     *
     * @return void
     */
    public function test_profile_page_display()
    {
        // テスト用のユーザーを作成
        /** @var Authenticatable $user */
        $user = User::factory()->create([
            'name' => '山田太郎',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now()
        ]);

        // 出品者ユーザーを作成
        $seller = User::factory()->create([
            'name' => '佐藤花子',
            'email' => 'seller@example.com',
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

        // 出品商品を作成
        $listedItem = Exhibition::create([
            'name' => 'iPhone 13 Pro Max',
            'brand' => 'Apple',
            'price' => 150000,
            'detail' => '最新のiPhoneです。',
            'product_image' => 'images/test/iphone.jpg',
            'condition' => 'brand_new',
            'user_id' => $user->id,
            'category' => json_encode(['スマートフォン'])
        ]);

        // 購入商品を作成
        $purchasedItem = Exhibition::create([
            'name' => 'MacBook Pro',
            'brand' => 'Apple',
            'price' => 200000,
            'detail' => '最新のMacBookです。',
            'product_image' => 'images/test/macbook.jpg',
            'condition' => 'brand_new',
            'user_id' => $seller->id,  // 出品者ユーザーのIDを使用
            'category' => json_encode(['ノートPC'])
        ]);

        // 購入情報を登録
        $purchase = Purchase::create([
            'user_id' => $user->id,
            'exhibition_id' => $purchasedItem->id,
            'address_id' => $address->id,  // 作成した住所のIDを使用
            'amount' => $purchasedItem->price,
            'payment_method' => '1'
        ]);

        // 1. ユーザーにログイン
        $this->actingAs($user);

        // 2. プロフィールページを開く（出品商品タブ）
        $response = $this->get(route('mypage', ['tab' => 'sell']));

        // レスポンスの確認（出品商品タブ）
        $response->assertStatus(200)
            // プロフィール情報の確認
            ->assertSee('storage/images/profile.png')  // デフォルトのプロフィール画像
            ->assertSee('山田太郎')  // ユーザー名
            // 出品商品の確認
            ->assertSee('iPhone 13 Pro Max')  // 出品商品名
            ->assertSee('images/test/iphone.jpg');  // 出品商品画像

        // 3. プロフィールページを開く（購入商品タブ）
        $response = $this->get(route('mypage', ['tab' => 'buy']));

        // レスポンスの確認（購入商品タブ）
        $response->assertStatus(200)
            // プロフィール情報の確認
            ->assertSee('storage/images/profile.png')  // デフォルトのプロフィール画像
            ->assertSee('山田太郎')  // ユーザー名
            // 購入商品の確認
            ->assertSee('MacBook Pro')  // 購入商品名
            ->assertSee('images/test/macbook.jpg');  // 購入商品画像
    }
}

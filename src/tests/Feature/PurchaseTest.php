<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Exhibition;
use App\Models\Address;
use App\Models\Purchase;
use Illuminate\Contracts\Auth\Authenticatable;

class PurchaseTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 商品購入の基本フローテスト
     * 1. ユーザーにログインする
     * 2. 商品購入画面を開く
     * 3. 商品を選択して「購入する」ボタンを押下
     * 期待挙動: 購入が完了する
     *
     * @return void
     */
    public function test_basic_purchase_flow()
    {
        // テスト用のユーザーを作成
        /** @var Authenticatable $user */
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now()
        ]);

        // テスト用の商品を作成
        $item = Exhibition::create([
            'name' => 'iPhone 13 Pro Max',
            'brand' => 'Apple',
            'price' => 150000,
            'detail' => '最新のiPhoneです。',
            'product_image' => 'images/test/iphone.jpg',
            'condition' => 'brand_new',
            'user_id' => $user->id,
            'category' => json_encode(['スマートフォン'])
        ]);

        // テスト用の配送先を作成
        $address = Address::create([
            'user_id' => $user->id,
            'name' => '山田太郎',
            'post_code' => '123-4567',
            'address' => '東京都渋谷区渋谷1-1-1',
            'building' => 'マンション101',
        ]);

        // 1. ユーザーにログイン
        $this->actingAs($user);

        // 2. 商品購入画面を開く
        $response = $this->get("/purchase/{$item->id}");
        $response->assertStatus(200)
            ->assertSee($item->name)
            ->assertSee(number_format($item->price))
            ->assertSee($address->post_code)
            ->assertSee($address->address);

        // 3. 商品を選択して「購入する」ボタンを押下
        $response = $this->post("/purchase/complete", [
            'exhibition_id' => $item->id,
            'quantity' => 1,
            'address_id' => $address->id,
            'payment_method' => '1'
        ]);

        // レスポンスの確認
        $response->assertRedirect("/item/{$item->id}")
            ->assertSessionHas('success', '購入が完了しました！');

        // データベースの確認
        $this->assertDatabaseHas('purchases', [
            'user_id' => $user->id,
            'exhibition_id' => $item->id,
            'address_id' => $address->id,
            'amount' => $item->price,
            'payment_method' => '1'
        ]);

        // 商品が売却済みになっていることを確認
        $this->assertTrue(Exhibition::find($item->id)->sold);
    }

    /**
     * 商品購入後の商品一覧表示テスト
     * 1. ユーザーにログインする
     * 2. 商品購入画面を開く
     * 3. 商品を選択して「購入する」ボタンを押下
     * 4. 商品一覧画面を表示する
     * 期待挙動: 購入した商品が「sold」として表示されている
     *
     * @return void
     */
    public function test_purchased_item_display_in_list()
    {
        // テスト用のユーザーを作成
        /** @var Authenticatable $user */
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now()
        ]);

        // テスト用の商品を作成
        $item = Exhibition::create([
            'name' => 'iPhone 13 Pro Max',
            'brand' => 'Apple',
            'price' => 150000,
            'detail' => '最新のiPhoneです。',
            'product_image' => 'images/test/iphone.jpg',
            'condition' => 'brand_new',
            'user_id' => $user->id,
            'category' => json_encode(['スマートフォン'])
        ]);

        // テスト用の配送先を作成
        $address = Address::create([
            'user_id' => $user->id,
            'name' => '山田太郎',
            'post_code' => '123-4567',
            'address' => '東京都渋谷区渋谷1-1-1',
            'building' => 'マンション101',
        ]);

        // 1. ユーザーにログイン
        $this->actingAs($user);

        // 2. 商品購入画面を開く
        $response = $this->get("/purchase/{$item->id}");
        $response->assertStatus(200);

        // 3. 商品を選択して「購入する」ボタンを押下
        $response = $this->post("/purchase/complete", [
            'exhibition_id' => $item->id,
            'quantity' => 1,
            'address_id' => $address->id,
            'payment_method' => '1'
        ]);

        // 4. 商品一覧画面を表示
        $response = $this->get('/');
        $response->assertStatus(200);

        // データベースの確認
        $this->assertDatabaseHas('exhibitions', [
            'id' => $item->id,
            'sold' => true
        ]);
    }

    /**
     * 商品購入後のプロフィール表示テスト
     * 1. ユーザーにログインする
     * 2. 商品購入画面を開く
     * 3. 商品を選択して「購入する」ボタンを押下
     * 4. プロフィール画面を表示する
     * 期待挙動: 購入した商品がプロフィールの購入した商品一覧に追加されている
     *
     * @return void
     */
    public function test_purchased_item_display_in_profile()
    {
        // テスト用のユーザーを作成
        /** @var Authenticatable $user */
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now()
        ]);

        // テスト用の商品を作成
        $item = Exhibition::create([
            'name' => 'iPhone 13 Pro Max',
            'brand' => 'Apple',
            'price' => 150000,
            'detail' => '最新のiPhoneです。',
            'product_image' => 'images/test/iphone.jpg',
            'condition' => 'brand_new',
            'user_id' => $user->id,
            'category' => json_encode(['スマートフォン'])
        ]);

        // テスト用の配送先を作成
        $address = Address::create([
            'user_id' => $user->id,
            'name' => '山田太郎',
            'post_code' => '123-4567',
            'address' => '東京都渋谷区渋谷1-1-1',
            'building' => 'マンション101',
        ]);

        // 1. ユーザーにログイン
        $this->actingAs($user);

        // 2. 商品購入画面を開く
        $response = $this->get("/purchase/{$item->id}");
        $response->assertStatus(200);

        // 3. 商品を選択して「購入する」ボタンを押下
        $response = $this->post("/purchase/complete", [
            'exhibition_id' => $item->id,
            'quantity' => 1,
            'address_id' => $address->id,
            'payment_method' => '1'
        ]);

        // 4. プロフィール画面を表示
        $response = $this->get('/mypage?tab=buy');
        $response->assertStatus(200)
            ->assertSee('購入した商品')
            ->assertSee($item->name);

        // データベースの確認
        $this->assertDatabaseHas('purchases', [
            'user_id' => $user->id,
            'exhibition_id' => $item->id,
            'amount' => $item->price
        ]);
    }
}

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

class PaymentMethodTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 支払い方法選択のテスト
     * 1. 支払い方法選択画面を開く
     * 2. プルダウンメニューから支払い方法を選択する
     * 期待挙動: 選択した支払い方法が正しく反映される
     *
     * @return void
     */
    public function test_payment_method_selection()
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

        // 2. 支払い方法選択画面を開く
        $response = $this->get("/purchase/{$item->id}");
        $response->assertStatus(200)
            ->assertSee('支払い方法')
            ->assertSee('コンビニ払い')
            ->assertSee('カード払い');

        // 3. 支払い方法を選択して「購入する」ボタンを押下
        $response = $this->post("/purchase/complete", [
            'exhibition_id' => $item->id,
            'quantity' => 1,
            'address_id' => $address->id,
            'payment_method' => '1' // コンビニ払い
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

        // 別の商品を作成して2回目のテスト
        $item2 = Exhibition::create([
            'name' => 'MacBook Pro',
            'brand' => 'Apple',
            'price' => 200000,
            'detail' => '最新のMacBookです。',
            'product_image' => 'images/test/macbook.jpg',
            'condition' => 'brand_new',
            'user_id' => $user->id,
            'category' => json_encode(['ノートPC'])
        ]);

        // 支払い方法選択画面を開く
        $response = $this->get("/purchase/{$item2->id}");
        $response->assertStatus(200)
            ->assertSee('支払い方法')
            ->assertSee('コンビニ払い')
            ->assertSee('カード払い');

        // 別の支払い方法で購入
        $response = $this->post("/purchase/complete", [
            'exhibition_id' => $item2->id,
            'quantity' => 1,
            'address_id' => $address->id,
            'payment_method' => '2' // カード払い
        ]);

        // データベースの確認
        $this->assertDatabaseHas('purchases', [
            'user_id' => $user->id,
            'exhibition_id' => $item2->id,
            'address_id' => $address->id,
            'amount' => $item2->price,
            'payment_method' => '2'
        ]);
    }
}

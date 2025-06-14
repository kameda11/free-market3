<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Address;
use App\Models\Exhibition;
use App\Models\Purchase;
use Illuminate\Contracts\Auth\Authenticatable;

class AddressTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 配送先住所の登録と商品購入画面での反映テスト
     * 1. ユーザーにログインする
     * 2. 送付先住所変更画面で住所を登録する
     * 3. 商品購入画面を再度開く
     * 期待挙動: 登録した住所が商品購入画面に正しく反映される
     *
     * @return void
     */
    public function test_address_registration_and_purchase_reflection()
    {
        // テスト用のユーザーを作成
        /** @var Authenticatable $user */
        $user = User::factory()->create([
            'name' => '山田太郎',
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

        // 1. ユーザーにログイン
        $this->actingAs($user);

        // 2. 送付先住所変更画面で住所を登録
        $response = $this->put(route('address.update'), [
            'post_code' => '123-4567',
            'address' => '東京都渋谷区渋谷1-1-1',
            'building' => 'マンション101',
            'item_id' => $item->id
        ]);

        // レスポンスの確認
        $response->assertRedirect(route('purchase', ['exhibition_id' => $item->id]))
            ->assertSessionHas('success', '住所を更新しました');

        // データベースの確認
        $this->assertDatabaseHas('addresses', [
            'user_id' => $user->id,
            'post_code' => '123-4567',
            'address' => '東京都渋谷区渋谷1-1-1',
            'building' => 'マンション101'
        ]);

        // 3. 商品購入画面を再度開く
        $response = $this->get(route('purchase', ['exhibition_id' => $item->id]));
        $response->assertStatus(200)
            ->assertSee('123-4567')
            ->assertSee('東京都渋谷区渋谷1-1-1')
            ->assertSee('マンション101');
    }

    /**
     * 配送先住所の登録と商品購入時の紐付けテスト
     * 1. ユーザーにログインする
     * 2. 送付先住所変更画面で住所を登録する
     * 3. 商品を購入する
     * 期待挙動: 正しく送付先住所が紐づいている
     *
     * @return void
     */
    public function test_address_registration_and_purchase_association()
    {
        // テスト用のユーザーを作成
        /** @var Authenticatable $user */
        $user = User::factory()->create([
            'name' => '山田太郎',
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

        // 1. ユーザーにログイン
        $this->actingAs($user);

        // 2. 送付先住所変更画面で住所を登録
        $response = $this->put(route('address.update'), [
            'post_code' => '123-4567',
            'address' => '東京都渋谷区渋谷1-1-1',
            'building' => 'マンション101',
            'item_id' => $item->id
        ]);

        // レスポンスの確認
        $response->assertRedirect(route('purchase', ['exhibition_id' => $item->id]))
            ->assertSessionHas('success', '住所を更新しました');

        // 登録した住所を取得
        $address = Address::where('user_id', $user->id)->first();

        // 3. 商品を購入
        $response = $this->post("/purchase/complete", [
            'exhibition_id' => $item->id,
            'quantity' => 1,
            'address_id' => $address->id,
            'payment_method' => '1' // コンビニ払い
        ]);

        // レスポンスの確認
        $response->assertRedirect("/item/{$item->id}")
            ->assertSessionHas('success', '購入が完了しました！');

        // 購入情報の確認
        $this->assertDatabaseHas('purchases', [
            'user_id' => $user->id,
            'exhibition_id' => $item->id,
            'address_id' => $address->id,
            'amount' => $item->price,
            'payment_method' => '1'
        ]);

        // 購入情報から住所情報を取得して確認
        $purchase = Purchase::where('user_id', $user->id)
            ->where('exhibition_id', $item->id)
            ->first();

        $this->assertEquals($address->id, $purchase->address_id);
        $this->assertEquals($address->name, $purchase->address->name);
        $this->assertEquals($address->post_code, $purchase->address->post_code);
        $this->assertEquals($address->address, $purchase->address->address);
    }
}

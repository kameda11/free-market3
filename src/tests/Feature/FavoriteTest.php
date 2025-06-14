<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Exhibition;
use App\Models\Favorite;
use Illuminate\Contracts\Auth\Authenticatable;

class FavoriteTest extends TestCase
{
    use RefreshDatabase;

    /**
     * いいね機能のテスト
     * 1. ユーザーにログインする
     * 2. 商品詳細ページを開く
     * 3. いいねアイコンを押下
     * 期待挙動: いいねした商品として登録され、いいね合計値が増加表示される
     *
     * @return void
     */
    public function test_add_favorite_to_item()
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

        // 1. ユーザーにログイン
        $this->actingAs($user);

        // 2. 商品詳細ページを開く
        $response = $this->get("/item/{$item->id}");
        $response->assertStatus(200);

        // 3. いいねアイコンを押下
        $response = $this->postJson('/favorites/toggle', [
            'exhibition_id' => $item->id
        ]);

        // レスポンスの確認
        $response->assertStatus(200)
            ->assertJson([
                'status' => 'added',
                'count' => 1
            ]);

        // データベースにいいねが保存されていることを確認
        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->id,
            'exhibition_id' => $item->id
        ]);

        // 商品詳細ページでいいね数が表示されていることを確認
        $response = $this->get("/item/{$item->id}");
        $response->assertStatus(200)
            ->assertSee('1');
    }

    /**
     * いいねアイコンの色変化テスト
     * 1. ユーザーにログインする
     * 2. 商品詳細ページを開く
     * 3. いいねアイコンを押下
     * 期待挙動: いいねアイコンが押下された状態では色が変化する
     *
     * @return void
     */
    public function test_favorite_icon_color_change()
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

        // 1. ユーザーにログイン
        $this->actingAs($user);

        // 2. 商品詳細ページを開く
        $response = $this->get("/item/{$item->id}");
        $response->assertStatus(200);

        // いいね前のアイコンの状態を確認
        $response->assertSee('far fa-star')
            ->assertDontSee('fas fa-star');

        // 3. いいねアイコンを押下
        $response = $this->postJson('/favorites/toggle', [
            'exhibition_id' => $item->id
        ]);

        // レスポンスの確認
        $response->assertStatus(200)
            ->assertJson([
                'status' => 'added',
                'count' => 1
            ]);

        // いいね後のアイコンの状態を確認
        $response = $this->get("/item/{$item->id}");
        $response->assertStatus(200)
            ->assertSee('fas fa-star')
            ->assertDontSee('far fa-star');
    }

    /**
     * いいね解除のテスト
     * 1. ユーザーにログインする
     * 2. 商品詳細ページを開く
     * 3. いいねアイコンを押下
     * 期待挙動: いいねが解除され、いいね合計値が減少表示される
     *
     * @return void
     */
    public function test_remove_favorite_from_item()
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

        // 1. ユーザーにログイン
        $this->actingAs($user);

        // 2. 商品詳細ページを開く
        $response = $this->get("/item/{$item->id}");
        $response->assertStatus(200);

        // 最初にいいねを追加
        $response = $this->postJson('/favorites/toggle', [
            'exhibition_id' => $item->id
        ]);

        // いいねが追加されたことを確認
        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->id,
            'exhibition_id' => $item->id
        ]);

        // 3. いいねアイコンを再度押下（いいね解除）
        $response = $this->postJson('/favorites/toggle', [
            'exhibition_id' => $item->id
        ]);

        // レスポンスの確認
        $response->assertStatus(200)
            ->assertJson([
                'status' => 'removed',
                'count' => 0
            ]);

        // データベースからいいねが削除されていることを確認
        $this->assertDatabaseMissing('favorites', [
            'user_id' => $user->id,
            'exhibition_id' => $item->id
        ]);

        // 商品詳細ページでいいね数が0になっていることを確認
        $response = $this->get("/item/{$item->id}");
        $response->assertStatus(200)
            ->assertSee('0');

        // いいねアイコンの状態が解除されていることを確認
        $response->assertSee('far fa-star')
            ->assertDontSee('fas fa-star');
    }
}
 
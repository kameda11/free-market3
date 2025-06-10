<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Exhibition;
use App\Models\Comment;
use App\Models\Favorite;
use Illuminate\Contracts\Auth\Authenticatable;

class ItemDetailTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 商品詳細ページの表示テスト
     * 1. 商品詳細ページを開く
     * 2. 必要な情報が全て表示されることを確認
     *
     * @return void
     */
    public function test_item_detail_display()
    {
        // テスト用の出品者を作成
        /** @var Authenticatable $seller */
        $seller = User::factory()->create([
            'email' => 'seller@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now()
        ]);

        // テスト用のコメント投稿者を作成
        /** @var Authenticatable $commenter */
        $commenter = User::factory()->create([
            'email' => 'commenter@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now()
        ]);

        // テスト用のいいねしたユーザーを作成
        /** @var Authenticatable $liker */
        $liker = User::factory()->create([
            'email' => 'liker@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now()
        ]);

        // テスト用の商品を作成
        $item = Exhibition::create([
            'name' => 'iPhone 13 Pro Max',
            'brand' => 'Apple',
            'price' => 150000,
            'detail' => '最新のiPhoneです。256GBモデルで、シルバーカラーです。',
            'product_image' => 'images/test/iphone.jpg',
            'condition' => 'brand_new',
            'user_id' => $seller->id,
            'category' => json_encode(['スマートフォン', 'Apple'])
        ]);

        // コメントを作成
        $comment = Comment::create([
            'exhibition_id' => $item->id,
            'user_id' => $commenter->id,
            'comment' => 'とても良い商品ですね！'
        ]);

        // いいねを作成
        Favorite::create([
            'exhibition_id' => $item->id,
            'user_id' => $liker->id
        ]);

        // 商品詳細ページにアクセス
        $response = $this->get("/item/{$item->id}");
        $response->assertStatus(200);

        // 商品画像が表示されることを確認
        $response->assertSee('images/test/iphone.jpg');

        // 商品名とブランド名が表示されることを確認
        $response->assertSee('iPhone 13 Pro Max');
        $response->assertSee('Apple');

        // 価格が表示されることを確認
        $response->assertSee('150,000');
        $response->assertSee('(税込)');

        // いいね数が表示されることを確認
        $response->assertSee('1');

        // 商品説明が表示されることを確認
        $response->assertSee('最新のiPhoneです。256GBモデルで、シルバーカラーです。');

        // 商品情報（カテゴリ、商品の状態）が表示されることを確認
        $response->assertSee('スマートフォン');
        $response->assertSee('Apple');
        $response->assertSee('新品・未使用');

        // コメント数が表示されることを確認
        $response->assertSee('1');

        // コメントしたユーザー情報とコメント内容が表示されることを確認
        $response->assertSee($commenter->name);
        $response->assertSee('とても良い商品ですね！');
    }

    /**
     * 複数カテゴリの表示テスト
     * 1. 複数のカテゴリを持つ商品を作成
     * 2. 商品詳細ページを開く
     * 3. 全てのカテゴリが表示されることを確認
     *
     * @return void
     */
    public function test_multiple_categories_display()
    {
        // テスト用の出品者を作成
        /** @var Authenticatable $user */
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now()
        ]);

        // 複数のカテゴリを持つ商品を作成
        $item = Exhibition::create([
            'name' => 'AirPods Pro',
            'brand' => 'Apple',
            'price' => 30000,
            'detail' => 'ノイズキャンセリング機能付きのワイヤレスイヤホンです。',
            'product_image' => 'images/test/airpods.jpg',
            'condition' => 'brand_new',
            'user_id' => $user->id,
            'category' => json_encode(['オーディオ', 'Apple', 'アクセサリー'])
        ]);

        // 商品詳細ページにアクセス
        $response = $this->get("/item/{$item->id}");
        $response->assertStatus(200);

        // 全てのカテゴリが表示されることを確認
        $response->assertSee('オーディオ');
        $response->assertSee('Apple');
        $response->assertSee('アクセサリー');

        // カテゴリの表示順序を確認（オプション）
        $content = $response->getContent();
        $this->assertStringContainsString('オーディオ', $content);
        $this->assertStringContainsString('Apple', $content);
        $this->assertStringContainsString('アクセサリー', $content);
    }
}

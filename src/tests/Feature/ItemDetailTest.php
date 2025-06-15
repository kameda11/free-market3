<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Exhibition;
use App\Models\Comment;
use App\Models\Favorite;
use Illuminate\Contracts\Auth\Authenticatable;

class ItemDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_item_detail_display()
    {
        /** @var Authenticatable $seller */
        $seller = User::factory()->create([
            'email' => 'seller@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now()
        ]);

        /** @var Authenticatable $commenter */
        $commenter = User::factory()->create([
            'email' => 'commenter@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now()
        ]);

        /** @var Authenticatable $liker */
        $liker = User::factory()->create([
            'email' => 'liker@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now()
        ]);

        $item = Exhibition::create([
            'name' => 'iPhone 13 Pro Max',
            'brand' => 'Apple',
            'price' => 150000,
            'detail' => '最新のiPhoneです。256GBモデルで、シルバーカラーです。',
            'product_image' => 'images/test/iphone.jpg',
            'condition' => 'brand_new',
            'user_id' => $seller->id,
            'category' => json_encode(['家電', 'メンズ'])
        ]);

        $comment = Comment::create([
            'exhibition_id' => $item->id,
            'user_id' => $commenter->id,
            'comment' => 'とても良い商品ですね！'
        ]);

        Favorite::create([
            'exhibition_id' => $item->id,
            'user_id' => $liker->id
        ]);

        $response = $this->get("/item/{$item->id}");
        $response->assertStatus(200);

        $response->assertSee('images/test/iphone.jpg');

        $response->assertSee('iPhone 13 Pro Max');
        $response->assertSee('Apple');

        $response->assertSee('150,000');
        $response->assertSee('(税込)');

        $response->assertSee('1');

        $response->assertSee('最新のiPhoneです。256GBモデルで、シルバーカラーです。');

        $response->assertSee('家電');
        $response->assertSee('メンズ');
        $response->assertSee('新品・未使用');

        $response->assertSee('1');

        $response->assertSee($commenter->name);
        $response->assertSee('とても良い商品ですね！');
    }

    public function test_multiple_categories_display()
    {
        /** @var Authenticatable $user */
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now()
        ]);

        $item = Exhibition::create([
            'name' => 'AirPods Pro',
            'brand' => 'Apple',
            'price' => 30000,
            'detail' => 'ノイズキャンセリング機能付きのワイヤレスイヤホンです。',
            'product_image' => 'images/test/airpods.jpg',
            'condition' => 'brand_new',
            'user_id' => $user->id,
            'category' => json_encode(['家電', 'アクセサリー'])
        ]);

        $response = $this->get("/item/{$item->id}");
        $response->assertStatus(200);

        $response->assertSee('家電');
        $response->assertSee('アクセサリー');

        $content = $response->getContent();
        $this->assertStringContainsString('家電', $content);
        $this->assertStringContainsString('アクセサリー', $content);
    }
}

<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Exhibition;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Auth\Authenticatable;

class SearchTest extends TestCase
{
    use RefreshDatabase;

    /**
     *
     * @return void
     */
    public function test_basic_search()
    {
        // テスト用のユーザーを作成
        /** @var Authenticatable $user */
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now()
        ]);

        // テスト用の商品を作成
        $items = [
            [
                'name' => 'iPhone 13 Pro Max',
                'price' => 150000,
                'detail' => '最新のiPhoneです',
                'product_image' => 'images/test/iphone.jpg',
                'condition' => 'brand_new',
                'user_id' => $user->id,
                'category' => json_encode(['スマートフォン'])
            ],
            [
                'name' => 'iPhone 12',
                'price' => 80000,
                'detail' => '前モデルのiPhoneです',
                'product_image' => 'images/test/iphone12.jpg',
                'condition' => 'used_good',
                'user_id' => $user->id,
                'category' => json_encode(['スマートフォン'])
            ],
            [
                'name' => 'MacBook Pro',
                'price' => 200000,
                'detail' => '高性能なノートPCです',
                'product_image' => 'images/test/macbook.jpg',
                'condition' => 'brand_new',
                'user_id' => $user->id,
                'category' => json_encode(['ノートPC'])
            ]
        ];

        foreach ($items as $item) {
            Exhibition::create($item);
        }

        // 検索フォームにアクセス
        $response = $this->get('/');
        $response->assertStatus(200);

        // 検索を実行（部分一致のテスト）
        $response = $this->get('/search?query=iPhone');

        // ステータスコードの確認
        $response->assertStatus(200);

        // 検索結果の確認
        // iPhoneを含む商品が表示されることを確認
        $response->assertSee('iPhone 13 Pro Max');
        $response->assertSee('iPhone 12');

        // iPhoneを含まない商品が表示されないことを確認
        $response->assertDontSee('MacBook Pro');

        // 検索フォームに検索キーワードが表示されることを確認
        $response->assertSee('value="iPhone"', false);
    }

    /**
     *
     * @return void
     */
    public function test_search_keyword_persistence()
    {
        // テスト用のユーザーを作成
        /** @var Authenticatable $user */
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now()
        ]);

        // テスト用の商品を作成
        $items = [
            [
                'name' => 'iPhone 13 Pro Max',
                'price' => 150000,
                'detail' => '最新のiPhoneです',
                'product_image' => 'images/test/iphone.jpg',
                'condition' => 'brand_new',
                'user_id' => $user->id,
                'category' => json_encode(['スマートフォン'])
            ],
            [
                'name' => 'iPhone 12',
                'price' => 80000,
                'detail' => '前モデルのiPhoneです',
                'product_image' => 'images/test/iphone12.jpg',
                'condition' => 'used_good',
                'user_id' => $user->id,
                'category' => json_encode(['スマートフォン'])
            ]
        ];

        $exhibitions = [];
        foreach ($items as $item) {
            $exhibitions[] = Exhibition::create($item);
        }

        // いいねした商品を保存
        DB::table('favorites')->insert([
            'user_id' => $user->id,
            'exhibition_id' => $exhibitions[0]->id
        ]);

        // ユーザーとしてログイン
        $this->actingAs($user);

        // 1. ホームページで商品を検索
        $response = $this->get('/search?query=iPhone');
        $response->assertStatus(200);

        // 2. 検索結果が表示されることを確認
        $response->assertSee('iPhone 13 Pro Max');
        $response->assertSee('iPhone 12');

        // 3. マイリストページに遷移
        $response = $this->post('/store-tab', [
            'tab' => 'favorites'
        ], [
            'X-CSRF-TOKEN' => csrf_token(),
            'Accept' => 'application/json'
        ]);

        // マイリストページで検索キーワードが保持されていることを確認
        $response = $this->withSession(['active_tab' => 'favorites'])
            ->get('/search?query=iPhone');

        // マイリストタブがアクティブであることを確認
        $content = $response->getContent();
        $this->assertStringContainsString('id="favorites"', $content);
        $this->assertStringContainsString('class="header__search-input"', $content);

        // 検索フォームに検索キーワードが表示されることを確認
        $response->assertSee('value="iPhone"', false);

        // マイリストタブでは、いいねした商品のみが表示されることを確認
        $favoritesSection = substr($content, strpos($content, 'id="favorites"'));
        $favoritesSection = substr($favoritesSection, 0, strpos($favoritesSection, '</div>'));
        $this->assertStringContainsString('iPhone 13 Pro Max', $favoritesSection);
        $this->assertStringNotContainsString('iPhone 12', $favoritesSection);
    }
}

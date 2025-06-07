<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Exhibition;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Auth\Authenticatable;

class ItemListTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 商品一覧が正しく表示される場合のテスト
     *
     * @return void
     */
    public function test_item_list_display()
    {
        // テスト用のユーザーを作成
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now()
        ]);

        // テスト用の商品を作成
        $items = [
            [
                'name' => 'テスト商品1',
                'price' => 1000,
                'detail' => 'テスト商品1の説明',
                'product_image' => 'images/test/item1.jpg',
                'condition' => 'brand_new',
                'user_id' => $user->id,
                'category' => json_encode(['カテゴリ1'])
            ],
            [
                'name' => 'テスト商品2',
                'price' => 2000,
                'detail' => 'テスト商品2の説明',
                'product_image' => 'images/test/item2.jpg',
                'condition' => 'used_good',
                'user_id' => $user->id,
                'category' => json_encode(['カテゴリ2'])
            ]
        ];

        foreach ($items as $item) {
            Exhibition::create($item);
        }

        // 商品一覧ページにアクセス
        $response = $this->get('/');

        // ステータスコードの確認
        $response->assertStatus(200);

        // 商品が表示されていることを確認
        $response->assertSee('テスト商品1');
        $response->assertSee('テスト商品2');
    }

    /**
     * 購入済み商品にSoldラベルが表示される場合のテスト
     *
     * @return void
     */
    public function test_sold_item_display()
    {
        // テスト用のユーザーを作成
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now()
        ]);

        // テスト用の商品を作成
        $items = [
            [
                'name' => '販売中商品',
                'price' => 1000,
                'detail' => '販売中商品の説明',
                'product_image' => 'images/test/active.jpg',
                'condition' => 'brand_new',
                'user_id' => $user->id,
                'category' => json_encode(['カテゴリ1']),
                'sold' => false
            ],
            [
                'name' => '購入済み商品',
                'price' => 2000,
                'detail' => '購入済み商品の説明',
                'product_image' => 'images/test/sold.jpg',
                'condition' => 'used_good',
                'user_id' => $user->id,
                'category' => json_encode(['カテゴリ2']),
                'sold' => true
            ]
        ];

        foreach ($items as $item) {
            Exhibition::create($item);
        }

        // 商品一覧ページにアクセス
        $response = $this->get('/');

        // ステータスコードの確認
        $response->assertStatus(200);

        // 商品が表示されていることを確認
        $response->assertSee('販売中商品');
        $response->assertSee('購入済み商品');

        // Soldラベルの表示を確認
        $response->assertSee('Sold');
    }

    /**
     * 自分が出品した商品は表示されない場合のテスト
     *
     * @return void
     */
    public function test_own_items_not_displayed()
    {
        // テスト用のユーザーを作成
        /** @var Authenticatable $user */
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now()
        ]);

        // 他のユーザーを作成
        $otherUser = User::factory()->create([
            'email' => 'other@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now()
        ]);

        // テスト用の商品を作成
        $items = [
            [
                'name' => '自分の出品商品',
                'price' => 1000,
                'detail' => '自分の出品商品の説明',
                'product_image' => 'images/test/own.jpg',
                'condition' => 'brand_new',
                'user_id' => $user->id,
                'category' => json_encode(['カテゴリ1'])
            ],
            [
                'name' => '他のユーザーの出品商品',
                'price' => 2000,
                'detail' => '他のユーザーの出品商品の説明',
                'product_image' => 'images/test/other.jpg',
                'condition' => 'used_good',
                'user_id' => $otherUser->id,
                'category' => json_encode(['カテゴリ2'])
            ]
        ];

        foreach ($items as $item) {
            Exhibition::create($item);
        }

        // ユーザーとしてログイン
        $this->actingAs($user);

        // トップページにアクセス
        $response = $this->get('/');

        // ステータスコードの確認
        $response->assertStatus(200);

        // 自分の出品商品が表示されないことを確認
        $response->assertDontSee('自分の出品商品');

        // 他のユーザーの出品商品が表示されることを確認
        $response->assertSee('他のユーザーの出品商品');
    }
}

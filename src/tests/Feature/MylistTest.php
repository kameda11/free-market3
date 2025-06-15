<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Exhibition;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Auth\Authenticatable;

class MylistTest extends TestCase
{
    use RefreshDatabase;

    /**
     * マイリスト（いいねした商品）が表示される場合のテスト
     *
     * @return void
     */
    public function test_mylist_display()
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
                'name' => 'いいねした商品1',
                'price' => 1000,
                'detail' => 'いいねした商品1の説明',
                'product_image' => 'images/test/favorite1.jpg',
                'condition' => 'brand_new',
                'user_id' => $user->id,
                'category' => json_encode(['カテゴリ1'])
            ],
            [
                'name' => 'いいねした商品2',
                'price' => 2000,
                'detail' => 'いいねした商品2の説明',
                'product_image' => 'images/test/favorite2.jpg',
                'condition' => 'used_good',
                'user_id' => $user->id,
                'category' => json_encode(['カテゴリ2'])
            ],
            [
                'name' => 'いいねしていない商品',
                'price' => 3000,
                'detail' => 'いいねしていない商品の説明',
                'product_image' => 'images/test/not_favorite.jpg',
                'condition' => 'brand_new',
                'user_id' => $user->id,
                'category' => json_encode(['カテゴリ3'])
            ]
        ];

        $exhibitions = [];
        foreach ($items as $item) {
            $exhibitions[] = Exhibition::create($item);
        }

        // いいねした商品を保存
        $favorites = [
            ['user_id' => $user->id, 'exhibition_id' => $exhibitions[0]->id],
            ['user_id' => $user->id, 'exhibition_id' => $exhibitions[1]->id]
        ];
        foreach ($favorites as $favorite) {
            DB::table('favorites')->insert($favorite);
        }

        // ユーザーとしてログイン
        $this->actingAs($user);

        // トップページにアクセス
        $response = $this->get('/');

        // ステータスコードの確認
        $response->assertStatus(200);

        // いいねした商品が表示されることを確認
        $response->assertSee('いいねした商品1');
        $response->assertSee('いいねした商品2');

        // いいねしていない商品が表示されないことを確認
        $response->assertDontSee('いいねしていない商品');
    }

    /**
     * マイリスト内の購入済み商品にSoldラベルが表示される場合のテスト
     *
     * @return void
     */
    public function test_sold_label_in_mylist()
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

        $exhibitions = [];
        foreach ($items as $item) {
            $exhibitions[] = Exhibition::create($item);
        }

        // いいねした商品を保存
        $favorites = [
            ['user_id' => $user->id, 'exhibition_id' => $exhibitions[0]->id],
            ['user_id' => $user->id, 'exhibition_id' => $exhibitions[1]->id]
        ];
        foreach ($favorites as $favorite) {
            DB::table('favorites')->insert($favorite);
        }

        // ユーザーとしてログイン
        $this->actingAs($user);

        // トップページにアクセス
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
     * 自分が出品した商品が商品一覧に表示されない場合のテスト
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

    /**
     * 未認証時にマイリストページを開いた場合のテスト
     *
     * @return void
     */
    public function test_mylist_page_when_not_authenticated()
    {
        // テスト用のユーザーを作成
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now()
        ]);

        // テスト用の商品を作成
        $item = [
            'name' => 'いいねした商品',
            'price' => 1000,
            'detail' => 'いいねした商品の説明',
            'product_image' => 'images/test/favorite.jpg',
            'condition' => 'brand_new',
            'user_id' => $user->id,
            'category' => json_encode(['カテゴリ1'])
        ];

        $exhibition = Exhibition::create($item);

        // いいねした商品を保存
        DB::table('favorites')->insert([
            'user_id' => $user->id,
            'exhibition_id' => $exhibition->id
        ]);

        // 未認証状態でトップページにアクセス
        $response = $this->get('/');

        // ステータスコードの確認
        $response->assertStatus(200);

        // マイリストタブが表示されることを確認
        $response->assertSee('マイリスト');

        // マイリストタブをクリック
        $response = $this->get('/?tab=favorites');

        // マイリストの内容が空であることを確認
        $response->assertStatus(200);
        $response->assertDontSee('お気に入り登録している商品はありません。');
    }
}

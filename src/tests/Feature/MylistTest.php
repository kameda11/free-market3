<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Exhibition;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Auth\Authenticatable;

class MylistTest extends TestCase
{
    use RefreshDatabase;

    public function test_mylist_display()
    {
        /** @var Authenticatable $user */
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now()
        ]);

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

        $favorites = [
            ['user_id' => $user->id, 'exhibition_id' => $exhibitions[0]->id],
            ['user_id' => $user->id, 'exhibition_id' => $exhibitions[1]->id]
        ];
        foreach ($favorites as $favorite) {
            DB::table('favorites')->insert($favorite);
        }

        $this->actingAs($user);

        $response = $this->get('/');

        $response->assertStatus(200);

        $response->assertSee('いいねした商品1');
        $response->assertSee('いいねした商品2');

        $response->assertDontSee('いいねしていない商品');
    }

    public function test_sold_label_in_mylist()
    {
        /** @var Authenticatable $user */
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now()
        ]);

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

        $favorites = [
            ['user_id' => $user->id, 'exhibition_id' => $exhibitions[0]->id],
            ['user_id' => $user->id, 'exhibition_id' => $exhibitions[1]->id]
        ];
        foreach ($favorites as $favorite) {
            DB::table('favorites')->insert($favorite);
        }

        $this->actingAs($user);

        $response = $this->get('/');

        $response->assertStatus(200);

        $response->assertSee('販売中商品');
        $response->assertSee('購入済み商品');

        $response->assertSee('Sold');
    }

    public function test_own_items_not_displayed()
    {
        /** @var Authenticatable $user */
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now()
        ]);

        $otherUser = User::factory()->create([
            'email' => 'other@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now()
        ]);

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

        $this->actingAs($user);

        $response = $this->get('/');

        $response->assertStatus(200);

        $response->assertDontSee('自分の出品商品');

        $response->assertSee('他のユーザーの出品商品');
    }

    public function test_mylist_page_when_not_authenticated()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now()
        ]);

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

        DB::table('favorites')->insert([
            'user_id' => $user->id,
            'exhibition_id' => $exhibition->id
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);

        $response->assertSee('マイリスト');

        $response = $this->get('/?tab=favorites');

        $response->assertStatus(200);
        $response->assertDontSee('お気に入り登録している商品はありません。');
    }
}

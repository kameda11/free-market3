<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Exhibition;
use Illuminate\Contracts\Auth\Authenticatable;

class ItemListTest extends TestCase
{
    use RefreshDatabase;

    public function test_item_list_display()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now()
        ]);

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

        $response = $this->get('/');

        $response->assertStatus(200);

        $response->assertSee('テスト商品1');
        $response->assertSee('テスト商品2');
    }

    public function test_sold_item_display()
    {
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

        foreach ($items as $item) {
            Exhibition::create($item);
        }

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
}

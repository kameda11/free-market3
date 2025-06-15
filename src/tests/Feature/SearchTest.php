<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Exhibition;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Auth\Authenticatable;

class SearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_basic_search()
    {
        /** @var Authenticatable $user */
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now()
        ]);

        $items = [
            [
                'name' => 'iPhone 13 Pro Max',
                'price' => 150000,
                'detail' => '最新のiPhoneです',
                'product_image' => 'images/test/iphone.jpg',
                'condition' => 'brand_new',
                'user_id' => $user->id,
                'category' => json_encode(['家電'])
            ],
            [
                'name' => 'iPhone 12',
                'price' => 80000,
                'detail' => '前モデルのiPhoneです',
                'product_image' => 'images/test/iphone12.jpg',
                'condition' => 'used_good',
                'user_id' => $user->id,
                'category' => json_encode(['家電'])
            ],
            [
                'name' => 'MacBook Pro',
                'price' => 200000,
                'detail' => '高性能なノートPCです',
                'product_image' => 'images/test/macbook.jpg',
                'condition' => 'brand_new',
                'user_id' => $user->id,
                'category' => json_encode(['家電'])
            ]
        ];

        foreach ($items as $item) {
            Exhibition::create($item);
        }

        $response = $this->get('/');
        $response->assertStatus(200);

        $response = $this->get('/search?query=iPhone');

        $response->assertStatus(200);

        $response->assertSee('iPhone 13 Pro Max');
        $response->assertSee('iPhone 12');

        $response->assertDontSee('MacBook Pro');

        $response->assertSee('value="iPhone"', false);
    }

    /**
     *
     * @return void
     */
    public function test_search_keyword_persistence()
    {
        /** @var Authenticatable $user */
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now()
        ]);

        $items = [
            [
                'name' => 'iPhone 13 Pro Max',
                'price' => 150000,
                'detail' => '最新のiPhoneです',
                'product_image' => 'images/test/iphone.jpg',
                'condition' => 'brand_new',
                'user_id' => $user->id,
                'category' => json_encode(['家電'])
            ],
            [
                'name' => 'iPhone 12',
                'price' => 80000,
                'detail' => '前モデルのiPhoneです',
                'product_image' => 'images/test/iphone12.jpg',
                'condition' => 'used_good',
                'user_id' => $user->id,
                'category' => json_encode(['家電'])
            ]
        ];

        $exhibitions = [];
        foreach ($items as $item) {
            $exhibitions[] = Exhibition::create($item);
        }

        DB::table('favorites')->insert([
            'user_id' => $user->id,
            'exhibition_id' => $exhibitions[0]->id
        ]);

        $this->actingAs($user);

        $response = $this->get('/search?query=iPhone');
        $response->assertStatus(200);

        $response->assertSee('iPhone 13 Pro Max');
        $response->assertSee('iPhone 12');

        $response = $this->post('/store-tab', [
            'tab' => 'favorites'
        ], [
            'X-CSRF-TOKEN' => csrf_token(),
            'Accept' => 'application/json'
        ]);

        $response = $this->withSession(['active_tab' => 'favorites'])
            ->get('/search?query=iPhone');

        $content = $response->getContent();
        $this->assertStringContainsString('id="favorites"', $content);
        $this->assertStringContainsString('class="header__search-input"', $content);

        $response->assertSee('value="iPhone"', false);

        $favoritesSection = substr($content, strpos($content, 'id="favorites"'));
        $favoritesSection = substr($favoritesSection, 0, strpos($favoritesSection, '</div>'));
        $this->assertStringContainsString('iPhone 13 Pro Max', $favoritesSection);
        $this->assertStringNotContainsString('iPhone 12', $favoritesSection);
    }
}

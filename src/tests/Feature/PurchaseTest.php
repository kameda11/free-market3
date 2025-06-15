<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Exhibition;
use App\Models\Address;
use Illuminate\Contracts\Auth\Authenticatable;

class PurchaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_basic_purchase_flow()
    {
        /** @var Authenticatable $user */
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now()
        ]);

        $item = Exhibition::create([
            'name' => 'iPhone 13 Pro Max',
            'brand' => 'Apple',
            'price' => 150000,
            'detail' => '最新のiPhoneです。',
            'product_image' => 'images/test/iphone.jpg',
            'condition' => 'brand_new',
            'user_id' => $user->id,
            'category' => json_encode(['家電'])
        ]);

        $address = Address::create([
            'user_id' => $user->id,
            'name' => '山田太郎',
            'post_code' => '123-4567',
            'address' => '東京都渋谷区渋谷1-1-1',
            'building' => 'マンション101',
        ]);

        $this->actingAs($user);

        $response = $this->get("/purchase/{$item->id}");
        $response->assertStatus(200)
            ->assertSee($item->name)
            ->assertSee(number_format($item->price))
            ->assertSee($address->post_code)
            ->assertSee($address->address);

        $response = $this->post("/purchase/complete", [
            'exhibition_id' => $item->id,
            'quantity' => 1,
            'address_id' => $address->id,
            'payment_method' => '1'
        ]);

        $response->assertRedirect("/item/{$item->id}")
            ->assertSessionHas('success', '購入が完了しました！');

        $this->assertDatabaseHas('purchases', [
            'user_id' => $user->id,
            'exhibition_id' => $item->id,
            'address_id' => $address->id,
            'amount' => $item->price,
            'payment_method' => '1'
        ]);

        $this->assertTrue(Exhibition::find($item->id)->sold);
    }

    public function test_purchased_item_display_in_list()
    {
        /** @var Authenticatable $user */
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now()
        ]);

        $item = Exhibition::create([
            'name' => 'iPhone 13 Pro Max',
            'brand' => 'Apple',
            'price' => 150000,
            'detail' => '最新のiPhoneです。',
            'product_image' => 'images/test/iphone.jpg',
            'condition' => 'brand_new',
            'user_id' => $user->id,
            'category' => json_encode(['家電'])
        ]);

        $address = Address::create([
            'user_id' => $user->id,
            'name' => '山田太郎',
            'post_code' => '123-4567',
            'address' => '東京都渋谷区渋谷1-1-1',
            'building' => 'マンション101',
        ]);

        $this->actingAs($user);

        $response = $this->get("/purchase/{$item->id}");
        $response->assertStatus(200);

        $response = $this->post("/purchase/complete", [
            'exhibition_id' => $item->id,
            'quantity' => 1,
            'address_id' => $address->id,
            'payment_method' => '1'
        ]);

        $response = $this->get('/');
        $response->assertStatus(200);

        $this->assertDatabaseHas('exhibitions', [
            'id' => $item->id,
            'sold' => true
        ]);
    }

    public function test_purchased_item_display_in_profile()
    {
        /** @var Authenticatable $user */
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now()
        ]);

        $item = Exhibition::create([
            'name' => 'iPhone 13 Pro Max',
            'brand' => 'Apple',
            'price' => 150000,
            'detail' => '最新のiPhoneです。',
            'product_image' => 'images/test/iphone.jpg',
            'condition' => 'brand_new',
            'user_id' => $user->id,
            'category' => json_encode(['家電'])
        ]);

        $address = Address::create([
            'user_id' => $user->id,
            'name' => '山田太郎',
            'post_code' => '123-4567',
            'address' => '東京都渋谷区渋谷1-1-1',
            'building' => 'マンション101',
        ]);

        $this->actingAs($user);

        $response = $this->get("/purchase/{$item->id}");
        $response->assertStatus(200);

        $response = $this->post("/purchase/complete", [
            'exhibition_id' => $item->id,
            'quantity' => 1,
            'address_id' => $address->id,
            'payment_method' => '1'
        ]);

        $response = $this->get('/mypage?tab=buy');
        $response->assertStatus(200)
            ->assertSee('購入した商品')
            ->assertSee($item->name);

        $this->assertDatabaseHas('purchases', [
            'user_id' => $user->id,
            'exhibition_id' => $item->id,
            'amount' => $item->price
        ]);
    }
}

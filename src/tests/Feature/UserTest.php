<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Address;
use App\Models\Exhibition;
use App\Models\Purchase;
use Illuminate\Contracts\Auth\Authenticatable;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_display()
    {
        /** @var Authenticatable $user */
        $user = User::factory()->create([
            'name' => '山田太郎',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now()
        ]);

        $seller = User::factory()->create([
            'name' => '佐藤花子',
            'email' => 'seller@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now()
        ]);

        $address = Address::create([
            'user_id' => $user->id,
            'name' => '山田太郎',
            'post_code' => '123-4567',
            'address' => '東京都渋谷区渋谷1-1-1',
            'building' => 'マンション101'
        ]);

        $listedItem = Exhibition::create([
            'name' => 'iPhone 13 Pro Max',
            'brand' => 'Apple',
            'price' => 150000,
            'detail' => '最新のiPhoneです。',
            'product_image' => 'images/test/iphone.jpg',
            'condition' => 'brand_new',
            'user_id' => $user->id,
            'category' => json_encode(['家電'])
        ]);

        $purchasedItem = Exhibition::create([
            'name' => 'MacBook Pro',
            'brand' => 'Apple',
            'price' => 200000,
            'detail' => '最新のMacBookです。',
            'product_image' => 'images/test/macbook.jpg',
            'condition' => 'brand_new',
            'user_id' => $seller->id,
            'category' => json_encode(['家電'])
        ]);

        $purchase = Purchase::create([
            'user_id' => $user->id,
            'exhibition_id' => $purchasedItem->id,
            'address_id' => $address->id,
            'amount' => $purchasedItem->price,
            'payment_method' => '1'
        ]);

        $this->actingAs($user);

        $response = $this->get(route('mypage', ['tab' => 'sell']));

        $response->assertStatus(200)
            ->assertSee('storage/images/profile.png')
            ->assertSee('山田太郎')
            ->assertSee('iPhone 13 Pro Max')
            ->assertSee('images/test/iphone.jpg');

        $response = $this->get(route('mypage', ['tab' => 'buy']));

        $response->assertStatus(200)
            ->assertSee('storage/images/profile.png')
            ->assertSee('山田太郎')
            ->assertSee('MacBook Pro')
            ->assertSee('images/test/macbook.jpg');
    }
}

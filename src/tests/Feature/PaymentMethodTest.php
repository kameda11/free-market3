<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Exhibition;
use App\Models\Address;
use Illuminate\Contracts\Auth\Authenticatable;

class PaymentMethodTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_method_change_reflects_in_subtotal()
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

        $response = $this->get("/purchase/{$item->id}", [
            'quantity' => 1,
            'payment_method' => '1'
        ]);
        $response->assertStatus(200)
            ->assertSee('150,000')
            ->assertSee('コンビニ払い');

        $response = $this->get("/purchase/{$item->id}", [
            'quantity' => 1,
            'payment_method' => '2'
        ]);
        $response->assertStatus(200)
            ->assertSee('150,000')
            ->assertSee('カード払い');

        $response = $this->get("/purchase/{$item->id}", [
            'quantity' => 1,
            'payment_method' => '1'
        ]);
        $response->assertStatus(200)
            ->assertSee('150,000')
            ->assertSee('コンビニ払い');
    }
}

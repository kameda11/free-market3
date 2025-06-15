<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Address;
use App\Models\Exhibition;
use App\Models\Purchase;
use Illuminate\Contracts\Auth\Authenticatable;

class AddressTest extends TestCase
{
    use RefreshDatabase;

    public function test_address_registration_and_purchase_reflection()
    {
        /** @var Authenticatable $user */
        $user = User::factory()->create([
            'name' => '山田太郎',
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

        $this->actingAs($user);

        $response = $this->put(route('address.update'), [
            'post_code' => '123-4567',
            'address' => '東京都渋谷区渋谷1-1-1',
            'building' => 'マンション101',
            'item_id' => $item->id
        ]);

        $response->assertRedirect(route('purchase', ['exhibition_id' => $item->id]))
            ->assertSessionHas('success', '住所を更新しました');

        $this->assertDatabaseHas('addresses', [
            'user_id' => $user->id,
            'post_code' => '123-4567',
            'address' => '東京都渋谷区渋谷1-1-1',
            'building' => 'マンション101'
        ]);

        $response = $this->get(route('purchase', ['exhibition_id' => $item->id]));
        $response->assertStatus(200)
            ->assertSee('123-4567')
            ->assertSee('東京都渋谷区渋谷1-1-1')
            ->assertSee('マンション101');
    }

    public function test_address_registration_and_purchase_association()
    {
        /** @var Authenticatable $user */
        $user = User::factory()->create([
            'name' => '山田太郎',
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

        $this->actingAs($user);

        $response = $this->put(route('address.update'), [
            'post_code' => '123-4567',
            'address' => '東京都渋谷区渋谷1-1-1',
            'building' => 'マンション101',
            'item_id' => $item->id
        ]);

        $response->assertRedirect(route('purchase', ['exhibition_id' => $item->id]))
            ->assertSessionHas('success', '住所を更新しました');

        $address = Address::where('user_id', $user->id)->first();

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

        $purchase = Purchase::where('user_id', $user->id)
            ->where('exhibition_id', $item->id)
            ->first();

        $this->assertEquals($address->id, $purchase->address_id);
        $this->assertEquals($address->name, $purchase->address->name);
        $this->assertEquals($address->post_code, $purchase->address->post_code);
        $this->assertEquals($address->address, $purchase->address->address);
    }
}

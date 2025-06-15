<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Exhibition;
use Illuminate\Contracts\Auth\Authenticatable;

class FavoriteTest extends TestCase
{
    use RefreshDatabase;

    public function test_add_favorite_to_item()
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

        $this->actingAs($user);

        $response = $this->get("/item/{$item->id}");
        $response->assertStatus(200);

        $response = $this->postJson('/favorites/toggle', [
            'exhibition_id' => $item->id
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'added',
                'count' => 1
            ]);

        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->id,
            'exhibition_id' => $item->id
        ]);

        $response = $this->get("/item/{$item->id}");
        $response->assertStatus(200)
            ->assertSee('1');
    }

    public function test_favorite_icon_color_change()
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

        $this->actingAs($user);

        $response = $this->get("/item/{$item->id}");
        $response->assertStatus(200);

        $response->assertSee('far fa-star')
            ->assertDontSee('fas fa-star');

        $response = $this->postJson('/favorites/toggle', [
            'exhibition_id' => $item->id
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'added',
                'count' => 1
            ]);

        $response = $this->get("/item/{$item->id}");
        $response->assertStatus(200)
            ->assertSee('fas fa-star')
            ->assertDontSee('far fa-star');
    }

    public function test_remove_favorite_from_item()
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

        $this->actingAs($user);

        $response = $this->get("/item/{$item->id}");
        $response->assertStatus(200);

        $response = $this->postJson('/favorites/toggle', [
            'exhibition_id' => $item->id
        ]);

        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->id,
            'exhibition_id' => $item->id
        ]);

        $response = $this->postJson('/favorites/toggle', [
            'exhibition_id' => $item->id
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'removed',
                'count' => 0
            ]);

        $this->assertDatabaseMissing('favorites', [
            'user_id' => $user->id,
            'exhibition_id' => $item->id
        ]);

        $response = $this->get("/item/{$item->id}");
        $response->assertStatus(200)
            ->assertSee('0');

        $response->assertSee('far fa-star')
            ->assertDontSee('fas fa-star');
    }
}
 
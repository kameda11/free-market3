<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Address;
use Illuminate\Contracts\Auth\Authenticatable;

class UserProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_initial_display()
    {
        /** @var Authenticatable $user */
        $user = User::factory()->create([
            'name' => '山田太郎',
            'email' => 'test@example.com',
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

        $this->actingAs($user);

        $response = $this->get(route('profile.edit'));

        $response->assertStatus(200)
            ->assertSee('storage/images/profile.png')
            ->assertSee('山田太郎')
            ->assertSee('123-4567')
            ->assertSee('東京都渋谷区渋谷1-1-1')
            ->assertSee('マンション101')
            ->assertSee('value="山田太郎"', false)
            ->assertSee('value="123-4567"', false)
            ->assertSee('value="東京都渋谷区渋谷1-1-1"', false)
            ->assertSee('value="マンション101"', false);
    }
}

<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Exhibition;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Contracts\Auth\Authenticatable;

class ExhibitionTest extends TestCase
{
    use RefreshDatabase;

    public function test_exhibition_form_submission()
    {
        /** @var Authenticatable $user */
        $user = User::factory()->create([
            'name' => '山田太郎',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now()
        ]);

        $this->actingAs($user);

        $response = $this->get(route('exhibition.create'));
        $response->assertStatus(200);

        Storage::fake('public');
        $file = UploadedFile::fake()->create('test.jpg', 100, 'image/jpeg');

        $response = $this->post(route('exhibition.store'), [
            'name' => 'iPhone 13 Pro Max',
            'brand' => 'Apple',
            'price' => 150000,
            'detail' => '最新のiPhoneです。未使用で、箱も付属しています。',
            'product_image' => $file,
            'condition' => 'brand_new',
            'category' => ['家電']
        ]);

        $response->assertRedirect('/')
            ->assertSessionHas('success', '商品を出品しました！');

        $this->assertDatabaseHas('exhibitions', [
            'name' => 'iPhone 13 Pro Max',
            'brand' => 'Apple',
            'price' => 150000,
            'detail' => '最新のiPhoneです。未使用で、箱も付属しています。',
            'condition' => 'brand_new',
            'user_id' => $user->id
        ]);

        $exhibition = Exhibition::where('user_id', $user->id)->first();
        $this->assertEquals(['家電'], json_decode($exhibition->category));

        $this->assertTrue(Storage::disk('public')->exists('products/' . $file->hashName()));
    }
}

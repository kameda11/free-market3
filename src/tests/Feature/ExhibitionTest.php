<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Exhibition;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Contracts\Auth\Authenticatable;

class ExhibitionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 商品出品テスト
     * 1. ユーザーにログインする
     * 2. 商品出品画面を開く
     * 3. 各項目に適切な情報を入力して保存する
     * 期待挙動: 各項目が正しく保存されている
     *
     * @return void
     */
    public function test_exhibition_form_submission()
    {
        // テスト用のユーザーを作成
        /** @var Authenticatable $user */
        $user = User::factory()->create([
            'name' => '山田太郎',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now()
        ]);

        // 1. ユーザーにログイン
        $this->actingAs($user);

        // 2. 商品出品画面を開く
        $response = $this->get(route('exhibition.create'));
        $response->assertStatus(200);

        // 3. 商品情報を入力して保存
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

        // レスポンスの確認
        $response->assertRedirect('/')
            ->assertSessionHas('success', '商品を出品しました！');

        // データベースの確認
        $this->assertDatabaseHas('exhibitions', [
            'name' => 'iPhone 13 Pro Max',  // 商品名
            'brand' => 'Apple',  // ブランド
            'price' => 150000,  // 販売価格
            'detail' => '最新のiPhoneです。未使用で、箱も付属しています。',  // 商品の説明
            'condition' => 'brand_new',  // 商品の状態
            'user_id' => $user->id
        ]);

        // カテゴリーの確認
        $exhibition = Exhibition::where('user_id', $user->id)->first();
        $this->assertEquals(['家電'], json_decode($exhibition->category));

        // 商品画像の確認
        $this->assertTrue(Storage::disk('public')->exists('products/' . $file->hashName()));
    }
}

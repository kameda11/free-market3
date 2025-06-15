<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Exhibition;
use App\Models\Comment;
use Illuminate\Contracts\Auth\Authenticatable;

class CommentTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_comment_and_verify_count()
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

        $initialCommentCount = Comment::where('exhibition_id', $item->id)->count();

        $comment = 'これはテストコメントです。';

        $response = $this->postJson("/comments", [
            'exhibition_id' => $item->id,
            'comment' => $comment
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true
            ]);

        $this->assertDatabaseHas('comments', [
            'user_id' => $user->id,
            'exhibition_id' => $item->id,
            'comment' => $comment
        ]);

        $newCommentCount = Comment::where('exhibition_id', $item->id)->count();
        $this->assertEquals($initialCommentCount + 1, $newCommentCount, 'コメント数が1増加していません');

        $response = $this->get("/item/{$item->id}");
        $response->assertStatus(200)
            ->assertSee($comment)
            ->assertSee((string)$newCommentCount);
    }

    public function test_post_comment_without_login()
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

        $response = $this->get("/item/{$item->id}");
        $response->assertStatus(200);

        $initialCommentCount = Comment::where('exhibition_id', $item->id)->count();

        $comment = 'これはテストコメントです。';

        $response = $this->postJson("/comments", [
            'exhibition_id' => $item->id,
            'comment' => $comment
        ]);

        $response->assertStatus(401);

        $this->assertDatabaseMissing('comments', [
            'exhibition_id' => $item->id,
            'comment' => $comment
        ]);

        $newCommentCount = Comment::where('exhibition_id', $item->id)->count();
        $this->assertEquals($initialCommentCount, $newCommentCount, 'コメント数が変化しています');

        $response = $this->get("/item/{$item->id}");
        $response->assertStatus(200)
            ->assertDontSee($comment);
    }

    public function test_comment_length_validation()
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

        $initialCommentCount = Comment::where('exhibition_id', $item->id)->count();

        $comment = str_repeat('あ', 257);

        $response = $this->postJson("/comments", [
            'exhibition_id' => $item->id,
            'comment' => $comment
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['comment']);

        $this->assertDatabaseMissing('comments', [
            'exhibition_id' => $item->id,
            'comment' => $comment
        ]);

        $newCommentCount = Comment::where('exhibition_id', $item->id)->count();
        $this->assertEquals($initialCommentCount, $newCommentCount, 'コメント数が変化しています');

        $response = $this->get("/item/{$item->id}");
        $response->assertStatus(200)
            ->assertDontSee($comment);
    }

    public function test_empty_comment_validation()
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

        $initialCommentCount = Comment::where('exhibition_id', $item->id)->count();

        $response = $this->postJson("/comments", [
            'exhibition_id' => $item->id,
            'comment' => ''
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['comment']);

        $this->assertDatabaseMissing('comments', [
            'exhibition_id' => $item->id,
            'comment' => ''
        ]);

        $newCommentCount = Comment::where('exhibition_id', $item->id)->count();
        $this->assertEquals($initialCommentCount, $newCommentCount, 'コメント数が変化しています');

        $response = $this->get("/item/{$item->id}");
        $response->assertStatus(200)
            ->assertSee('商品へのコメント')
            ->assertSee('コメントを送信する')
            ->assertDontSee('コメントを投稿しました！');
    }
}
 
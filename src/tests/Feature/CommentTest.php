<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Exhibition;
use App\Models\Comment;
use Illuminate\Contracts\Auth\Authenticatable;

class CommentTest extends TestCase
{
    use RefreshDatabase;

    /**
     * コメント投稿のテスト
     * 1. ユーザーにログインする
     * 2. コメントを入力する
     * 3. コメントボタンを押す
     * 期待挙動: コメントが保存され、コメント数が増加する
     *
     * @return void
     */
    public function test_post_comment_and_verify_count()
    {
        // テスト用のユーザーを作成
        /** @var Authenticatable $user */
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now()
        ]);

        // テスト用の商品を作成
        $item = Exhibition::create([
            'name' => 'iPhone 13 Pro Max',
            'brand' => 'Apple',
            'price' => 150000,
            'detail' => '最新のiPhoneです。',
            'product_image' => 'images/test/iphone.jpg',
            'condition' => 'brand_new',
            'user_id' => $user->id,
            'category' => json_encode(['スマートフォン'])
        ]);

        // 1. ユーザーにログイン
        $this->actingAs($user);

        // 商品詳細ページを開く
        $response = $this->get("/item/{$item->id}");
        $response->assertStatus(200);

        // 初期のコメント数を取得
        $initialCommentCount = Comment::where('exhibition_id', $item->id)->count();

        // 2. コメントを入力
        $comment = 'これはテストコメントです。';

        // 3. コメントボタンを押す
        $response = $this->postJson("/comments", [
            'exhibition_id' => $item->id,
            'comment' => $comment
        ]);

        // レスポンスの確認
        $response->assertStatus(200)
            ->assertJson([
                'success' => true
            ]);

        // データベースにコメントが保存されていることを確認
        $this->assertDatabaseHas('comments', [
            'user_id' => $user->id,
            'exhibition_id' => $item->id,
            'comment' => $comment
        ]);

        // コメント数が増加していることを確認
        $newCommentCount = Comment::where('exhibition_id', $item->id)->count();
        $this->assertEquals($initialCommentCount + 1, $newCommentCount, 'コメント数が1増加していません');

        // 商品詳細ページでコメントが表示されていることを確認
        $response = $this->get("/item/{$item->id}");
        $response->assertStatus(200)
            ->assertSee($comment)
            ->assertSee((string)$newCommentCount);
    }

    /**
     * 未ログイン状態でのコメント投稿テスト
     * 1. コメントを入力する
     * 2. コメントボタンを押す
     * 期待挙動: コメントが送信されない
     *
     * @return void
     */
    public function test_post_comment_without_login()
    {
        // テスト用のユーザーを作成（商品作成用）
        /** @var Authenticatable $user */
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now()
        ]);

        // テスト用の商品を作成
        $item = Exhibition::create([
            'name' => 'iPhone 13 Pro Max',
            'brand' => 'Apple',
            'price' => 150000,
            'detail' => '最新のiPhoneです。',
            'product_image' => 'images/test/iphone.jpg',
            'condition' => 'brand_new',
            'user_id' => $user->id,
            'category' => json_encode(['スマートフォン'])
        ]);

        // 商品詳細ページを開く
        $response = $this->get("/item/{$item->id}");
        $response->assertStatus(200);

        // 初期のコメント数を取得
        $initialCommentCount = Comment::where('exhibition_id', $item->id)->count();

        // 1. コメントを入力
        $comment = 'これはテストコメントです。';

        // 2. コメントボタンを押す
        $response = $this->postJson("/comments", [
            'exhibition_id' => $item->id,
            'comment' => $comment
        ]);

        // 401 Unauthorizedエラーの確認
        $response->assertStatus(401);

        // データベースにコメントが保存されていないことを確認
        $this->assertDatabaseMissing('comments', [
            'exhibition_id' => $item->id,
            'comment' => $comment
        ]);

        // コメント数が変化していないことを確認
        $newCommentCount = Comment::where('exhibition_id', $item->id)->count();
        $this->assertEquals($initialCommentCount, $newCommentCount, 'コメント数が変化しています');

        // 商品詳細ページでコメントが表示されていないことを確認
        $response = $this->get("/item/{$item->id}");
        $response->assertStatus(200)
            ->assertDontSee($comment);
    }

    /**
     * コメント文字数制限のテスト
     * 1. ユーザーにログインする
     * 2. 256文字以上のコメントを入力する
     * 3. コメントボタンを押す
     * 期待挙動: バリデーションメッセージが表示される
     *
     * @return void
     */
    public function test_comment_length_validation()
    {
        // テスト用のユーザーを作成
        /** @var Authenticatable $user */
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now()
        ]);

        // テスト用の商品を作成
        $item = Exhibition::create([
            'name' => 'iPhone 13 Pro Max',
            'brand' => 'Apple',
            'price' => 150000,
            'detail' => '最新のiPhoneです。',
            'product_image' => 'images/test/iphone.jpg',
            'condition' => 'brand_new',
            'user_id' => $user->id,
            'category' => json_encode(['スマートフォン'])
        ]);

        // 1. ユーザーにログイン
        $this->actingAs($user);

        // 商品詳細ページを開く
        $response = $this->get("/item/{$item->id}");
        $response->assertStatus(200);

        // 初期のコメント数を取得
        $initialCommentCount = Comment::where('exhibition_id', $item->id)->count();

        // 2. 256文字以上のコメントを入力
        $comment = str_repeat('あ', 257); // 257文字のコメント

        // 3. コメントボタンを押す
        $response = $this->postJson("/comments", [
            'exhibition_id' => $item->id,
            'comment' => $comment
        ]);

        // バリデーションエラーの確認
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['comment']);

        // データベースにコメントが保存されていないことを確認
        $this->assertDatabaseMissing('comments', [
            'exhibition_id' => $item->id,
            'comment' => $comment
        ]);

        // コメント数が変化していないことを確認
        $newCommentCount = Comment::where('exhibition_id', $item->id)->count();
        $this->assertEquals($initialCommentCount, $newCommentCount, 'コメント数が変化しています');

        // 商品詳細ページでコメントが表示されていないことを確認
        $response = $this->get("/item/{$item->id}");
        $response->assertStatus(200)
            ->assertDontSee($comment);
    }

    /**
     * 空のコメント投稿テスト
     * 1. ユーザーにログインする
     * 2. コメントボタンを押す
     * 期待挙動: バリデーションメッセージが表示される
     *
     * @return void
     */
    public function test_empty_comment_validation()
    {
        // テスト用のユーザーを作成
        /** @var Authenticatable $user */
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now()
        ]);

        // テスト用の商品を作成
        $item = Exhibition::create([
            'name' => 'iPhone 13 Pro Max',
            'brand' => 'Apple',
            'price' => 150000,
            'detail' => '最新のiPhoneです。',
            'product_image' => 'images/test/iphone.jpg',
            'condition' => 'brand_new',
            'user_id' => $user->id,
            'category' => json_encode(['スマートフォン'])
        ]);

        // 1. ユーザーにログイン
        $this->actingAs($user);

        // 商品詳細ページを開く
        $response = $this->get("/item/{$item->id}");
        $response->assertStatus(200);

        // 初期のコメント数を取得
        $initialCommentCount = Comment::where('exhibition_id', $item->id)->count();

        // 2. コメントボタンを押す（空のコメント）
        $response = $this->postJson("/comments", [
            'exhibition_id' => $item->id,
            'comment' => ''
        ]);

        // バリデーションエラーの確認
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['comment']);

        // データベースにコメントが保存されていないことを確認
        $this->assertDatabaseMissing('comments', [
            'exhibition_id' => $item->id,
            'comment' => ''
        ]);

        // コメント数が変化していないことを確認
        $newCommentCount = Comment::where('exhibition_id', $item->id)->count();
        $this->assertEquals($initialCommentCount, $newCommentCount, 'コメント数が変化しています');

        // 商品詳細ページでコメントが表示されていないことを確認
        $response = $this->get("/item/{$item->id}");
        $response->assertStatus(200)
            ->assertSee('商品へのコメント')  // コメントフォームは表示されている
            ->assertSee('コメントを送信する')  // 送信ボタンも表示されている
            ->assertDontSee('コメントを投稿しました！');  // 成功メッセージは表示されていない
    }
}
 
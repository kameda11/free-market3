<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;

class ExhibitionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $filenames = ['1.jpg', '2.jpg', '3.jpg', '4.jpg', '5.jpg', '6.jpg', '7.jpg', '8.jpg', '9.jpg', '10.jpg'];

        // カテゴリーと状態の選択肢
        $categories = [
            'ファッション',
            '家電',
            'インテリア',
            'レディース',
            'メンズ',
            'コスメ',
            '本',
            'ゲーム',
            'スポーツ',
            'キッチン',
            'ハンドメイド',
            'アクセサリー',
            'おもちゃ',
            'ベビー・キッズ'
        ];

        $conditions = [
            'used_like_new',
            'used_good',
            'used_fair',
            'used_poor'
        ];

        return [
            'name' => $this->faker->words(2, true),
            'detail' => $this->faker->sentence(),
            'category' => $this->faker->randomElement($categories),
            'product_image' => 'products/' . $this->faker->randomElement($filenames), 
            'condition' => $this->faker->randomElement($conditions),
            'price' => $this->faker->numberBetween(1000, 10000),
            'brand' => $this->faker->company(), // ブランド名をランダム生成
            'user_id' => User::factory(),
        ];
    }
}

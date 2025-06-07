<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Exhibition;
use App\Models\User;

class ExhibitionTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // ユーザーIDを取得
        $userIds = User::pluck('id')->toArray();

        $exhibitions = [
            [
                'name' => 'メンズ腕時計',
                'price' => 15000,
                'detail' => 'スタイリッシュなデザインのメンズ腕時計',
                'product_image' => 'images/seed/watch.jpg',
                'condition' => 'brand_new',
                'user_id' => $userIds[0],
                'category' => json_encode(['メンズ', 'アクセサリー'])
            ],
            [
                'name' => 'HDD',
                'price' => 5000,
                'detail' => '高速で信頼性の高いハードディスク',
                'product_image' => 'images/seed/hdd.jpg',
                'condition' => 'used_good',
                'user_id' => $userIds[1],
                'category' => json_encode(['家電'])
            ],
            [
                'name' => '玉ねぎ３束',
                'price' => 300,
                'detail' => '新鮮な玉ねぎ３束のセット',
                'product_image' => 'images/seed/onion.jpg',
                'condition' => 'used_acceptable',
                'user_id' => $userIds[2],
                'category' => json_encode(['キッチン'])
            ],
            [
                'name' => '革靴',
                'price' => 4000,
                'detail' => 'クラシックなデザインの革靴',
                'product_image' => 'images/seed/shoes.jpg',
                'condition' => 'used_poor',
                'user_id' => $userIds[3],
                'category' => json_encode(['メンズ', 'ファッション'])
            ],
            [
                'name' => 'ノートPC',
                'price' => 45000,
                'detail' => '高機能なノートパソコン',
                'product_image' => 'images/seed/laptop.jpg',
                'condition' => 'used_like_new',
                'user_id' => $userIds[4],
                'category' => json_encode(['家電'])
            ],
            [
                'name' => 'マイク',
                'price' => 8000,
                'detail' => '高音質のレコーディング用マイク',
                'product_image' => 'images/seed/mic.jpg',
                'condition' => 'used_good',
                'user_id' => $userIds[5],
                'category' => json_encode(['家電'])
            ],
            [
                'name' => 'ショルダーバック',
                'price' => 3500,
                'detail' => 'おしゃれなショルダーバック',
                'product_image' => 'images/seed/bag.jpg',
                'condition' => 'used_acceptable',
                'user_id' => $userIds[6],
                'category' => json_encode(['レディース', 'ファッション'])
            ],
            [
                'name' => 'タンブラー',
                'price' => 500,
                'detail' => '使いやすいタンブラー',
                'product_image' => 'images/seed/tumbler.jpg',
                'condition' => 'used_poor',
                'user_id' => $userIds[7],
                'category' => json_encode(['キッチン'])
            ],
            [
                'name' => 'コーヒーミル',
                'price' => 4000,
                'detail' => '手動のコーヒーミル',
                'product_image' => 'images/seed/coffee.jpg',
                'condition' => 'brand_new',
                'user_id' => $userIds[8],
                'category' => json_encode(['キッチン'])
            ],
            [
                'name' => 'メイクセット',
                'price' => 2500,
                'detail' => '便利なメイクアップセット',
                'product_image' => 'images/seed/makeup.jpg',
                'condition' => 'used_like_new',
                'user_id' => $userIds[9],
                'category' => json_encode(['レディース', 'コスメ'])
            ]
        ];

        foreach ($exhibitions as $exhibition) {
            Exhibition::create($exhibition);
        }
    }
}

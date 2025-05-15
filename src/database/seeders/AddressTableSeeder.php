<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Address;
use App\Models\User;

class AddressTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $user = User::find(1);

        if ($user) {
            Address::create([
                'user_id' => $user->id,
                'name' => '山田 太郎',
                'post_code' => '123-4567',
                'address' => '東京都新宿区サンプル1-2-3',
                'building' => 'サンプルビル101',
            ]);
        }
    }
}

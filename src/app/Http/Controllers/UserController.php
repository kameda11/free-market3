<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use App\Models\Exhibition;
use App\Models\Address;
use App\Models\Profile;
use App\Http\Requests\ProfileRequest;
use App\Http\Requests\AddressRequest;
use App\Http\Requests\EditRequest;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function index()
    {
        // Exhibitionモデルからすべての出品データを取得
        $exhibitions = Exhibition::all();

        // ビューに渡す
        return view('index', compact('exhibitions'));
    }

    public function profile(Request $request)
    {
        $user = Auth::user();
        $user = User::with(['exhibitions', 'purchases.exhibition', 'address'])->find($user->id);
        $address = $user->address;

        // 出品した商品を取得（自分の出品のみ）
        $exhibitions = $user->exhibitions()->where('user_id', $user->id)->get();

        // 購入した商品を取得
        $purchases = $user->purchases->map(function ($purchase) {
            return $purchase->exhibition;
        });

        return view('profile', compact('user', 'address', 'exhibitions', 'purchases'));
    }

    public function editProfile()
    {
        $user = Auth::user();
        $address = $user->address;
        return view('edit', compact('user', 'address'));
    }

    public function updateProfile(EditRequest $request)
    {
        $user = Auth::user();

        if ($request->hasFile('profile_image')) {
            // 古い画像の削除
            if ($user->profile && $user->profile->profile_image) {
                Storage::delete('public/' . $user->profile->profile_image);
            }

            // 新しい画像の保存
            $imageName = time() . '.' . $request->profile_image->extension();
            $path = $request->profile_image->storeAs('public/profiles', $imageName);

            // プロフィール情報の更新または作成
            if (!$user->profile) {
                Profile::create([
                    'user_id' => $user->id,
                    'profile_image' => 'profiles/' . $imageName
                ]);
            } else {
                $user->profile->update([
                    'profile_image' => 'profiles/' . $imageName
                ]);
            }
        }

        // ユーザー情報の更新
        User::where('id', $user->id)->update([
            'name' => $request->name
        ]);

        // 住所情報の更新
        $address = $user->address ?? new Address();
        $address->user_id = $user->id;
        $address->name = $user->name;
        $address->post_code = $request->post_code;
        $address->address = $request->address;
        $address->building = $request->building;
        $address->save();

        return redirect()->route('mypage')->with('success', 'プロフィールを更新しました');
    }

    public function addresses()
    {
        $user = auth()->user();
        $address = $user->address ?? new Address(); // 住所が存在しない場合は新しいUserAddressインスタンスを作成
        return view('address', compact('user', 'address'));
    }

    public function edit($item_id)
    {
        $user = auth()->user();
        $address = $user->address;
        return view('edit', compact('user', 'address', 'item_id'));
    }

    public function updateAddress(AddressRequest $request)
    {
        $user = auth()->user();
        $address = $user->address ?? new Address();

        // 住所の更新
        $address->user_id = $user->id;
        $address->name = $user->name;  // ユーザー名を設定
        $address->post_code = $request->post_code;
        $address->address = $request->address;
        $address->building = $request->building;
        $address->save();

        // 商品IDがある場合は購入画面にリダイレクト
        if ($request->has('item_id')) {
            return redirect()->route('purchase', [
                'exhibition_id' => $request->item_id
            ])->with('success', '住所を更新しました');
        }

        // 商品IDがない場合は購入画面にリダイレクト
        return redirect()->route('purchase', [
            'exhibition_id' => 1  // デフォルトの商品ID
        ])->with('success', '住所を更新しました');
    }

    public function purchaseAddress($item_id)
    {
        $user = auth()->user();
        $address = $user->address ?? new Address();
        $exhibition = Exhibition::findOrFail($item_id);

        return view('address', compact('user', 'address', 'exhibition'));
    }
}

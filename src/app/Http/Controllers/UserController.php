<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use App\Models\Exhibition;
use App\Models\Address;
use App\Models\Profile;
use App\Http\Requests\AddressRequest;
use App\Http\Requests\EditRequest;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function index()
    {
        $exhibitions = Exhibition::all();

        return view('index', compact('exhibitions'));
    }

    public function profile(Request $request)
    {
        $user = Auth::user();
        $user = User::with(['exhibitions', 'purchases.exhibition', 'address'])->find($user->id);
        $address = $user->address;

        $exhibitions = $user->exhibitions()->where('user_id', $user->id)->get();

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
            if ($user->profile && $user->profile->profile_image) {
                Storage::delete('public/' . $user->profile->profile_image);
            }

            $imageName = time() . '.' . $request->profile_image->extension();
            $path = $request->profile_image->storeAs('public/profiles', $imageName);

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

        User::where('id', $user->id)->update([
            'name' => $request->name
        ]);

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
        $address = $user->address ?? new Address();
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

        $address->user_id = $user->id;
        $address->name = $user->name;
        $address->post_code = $request->post_code;
        $address->address = $request->address;
        $address->building = $request->building;
        $address->save();

        if ($request->has('item_id')) {
            return redirect()->route('purchase', [
                'exhibition_id' => $request->item_id
            ])->with('success', '住所を更新しました');
        }

        return redirect()->route('purchase', [
            'exhibition_id' => 1
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

<?php

namespace App\Http\Controllers;

use App\Http\Requests\PurchaseRequest;
use Illuminate\Http\Request;
use App\Models\Exhibition;
use App\Models\Favorite;
use App\Models\Address;
use App\Models\Purchase;
use App\Models\Comment;
use App\Http\Requests\ExhibitionRequest;
use App\Http\Requests\CommentRequest;
use Illuminate\Support\Facades\Auth;

class ItemController extends Controller
{
    public function add(Request $request)
    {
        $itemId = $request->input('item_id');
        $quantity = $request->input('quantity', 1);
        $item = Exhibition::findOrFail($itemId);
        $cart = session()->get('cart', []);

        if (isset($cart[$itemId])) {
            $cart[$itemId]['quantity'] += $quantity;
        } else {
            $cart[$itemId] = [
                'id' => $item->id,
                'name' => $item->name,
                'price' => $item->price,
                'image' => $item->product_image,
                'quantity' => $quantity,
            ];
        }

        session(['cart' => $cart]);
        return redirect()->back()->with('success', 'カートに追加しました。');
    }

    public function index()
    {
        $userId = Auth::id();
        $allExhibitions = Exhibition::query();

        if ($userId) {
            $allExhibitions = $allExhibitions->where('user_id', '!=', $userId);
        }

        $allExhibitions = $allExhibitions->get();
        $favoriteExhibitions = collect();

        if ($userId) {
            $favoriteExhibitions = Exhibition::join('favorites', 'exhibitions.id', '=', 'favorites.exhibition_id')
                ->where('favorites.user_id', $userId)
                ->select('exhibitions.*')
                ->with(['purchase', 'favorites', 'comments.user.profile'])
                ->get();
        }

        return view('index', compact('allExhibitions', 'favoriteExhibitions'));
    }

    public function create()
    {
        return view('sell');
    }

    public function store(ExhibitionRequest $request)
    {
        try {
            $validated = $request->validated();
            $path = $request->hasFile('product_image')
                ? $request->file('product_image')->store('products', 'public')
                : 'products/default.jpg';
            $categories = json_encode($validated['category']);

            $data = [
                'name' => $validated['name'],
                'brand' => $validated['brand'] ?? null,
                'detail' => $validated['detail'],
                'category' => $categories,
                'condition' => $validated['condition'],
                'price' => $validated['price'],
                'user_id' => auth()->id(),
                'product_image' => $path,
            ];

            Exhibition::create($data);
            return redirect('/')->with('success', '商品を出品しました！');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', '商品の出品に失敗しました。');
        }
    }

    public function __construct()
    {
        $this->middleware('auth')->only([
            'add',
            'create',
            'store',
            'storeComment',
            'storeFavorite',
            'toggle',
            'complete',
            'purchases'
        ]);
    }

    public function storeComment(CommentRequest $request)
    {
        try {
            $validated = $request->validated();
            Comment::create([
                'user_id' => Auth::id(),
                'exhibition_id' => $validated['exhibition_id'],
                'comment' => $validated['comment'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'コメントを投稿しました！'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'コメントの投稿に失敗しました。'
            ], 500);
        }
    }

    public function show($item_id)
    {
        $exhibition = Exhibition::with(['favorites', 'comments.user.profile'])->findOrFail($item_id);
        return view('detail', compact('exhibition'));
    }

    public function storeFavorite(Request $request)
    {
        $request->validate([
            'exhibition_id' => 'required|exists:exhibitions,id',
        ]);

        Favorite::firstOrCreate([
            'user_id' => Auth::id(),
            'exhibition_id' => $request->exhibition_id,
        ]);

        return back()->with('success', 'お気に入りに追加しました');
    }

    public function toggle(Request $request)
    {
        $request->validate([
            'exhibition_id' => 'required|exists:exhibitions,id',
        ]);

        $exhibitionId = $request->input('exhibition_id');
        $user = $request->user();
        $favorite = Favorite::where('user_id', $user->id)
            ->where('exhibition_id', $exhibitionId)
            ->first();

        $status = 'added';

        if ($favorite) {
            $favorite->delete();
            $status = 'removed';
        } else {
            Favorite::create([
                'user_id' => $user->id,
                'exhibition_id' => $exhibitionId,
            ]);
        }

        $count = Favorite::where('exhibition_id', $exhibitionId)->count();

        return response()->json([
            'status' => $status,
            'count' => $count,
        ]);
    }

    public function complete(PurchaseRequest $request)
    {
        try {
            $exhibitionId = $request->input('exhibition_id');
            $quantity = $request->input('quantity');
            $addressId = $request->input('address_id');
            $paymentMethod = $request->input('payment_method');
            $exhibition = Exhibition::findOrFail($exhibitionId);

            if ($exhibition->sold) {
                return redirect()->route('purchase', ['exhibition_id' => $exhibitionId])
                    ->with('error', 'この商品は既に購入されています。');
            }

            $purchase = Purchase::create([
                'user_id' => auth()->id(),
                'exhibition_id' => $exhibitionId,
                'address_id' => $addressId,
                'amount' => $exhibition->price * $quantity,
                'payment_method' => $paymentMethod,
            ]);

            $exhibition->sold = true;
            $exhibition->save();

            return redirect()->route('detail', ['item_id' => $exhibitionId])
                ->with('success', '購入が完了しました！');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', '購入処理中にエラーが発生しました。');
        }
    }

    public function purchases($exhibition_id)
    {
        $exhibition = Exhibition::findOrFail($exhibition_id);
        $quantity = 1;
        $user = auth()->user();
        $address = Address::where('user_id', $user->id)->first();

        return view('purchase', compact('exhibition', 'quantity', 'address'));
    }
}

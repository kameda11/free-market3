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
use Illuminate\Support\Facades\Log;

class ItemController extends Controller
{
    public function add(Request $request)
    {
        $itemId = $request->input('item_id');
        $quantity = $request->input('quantity', 1);

        // 商品情報を取得
        $item = Exhibition::findOrFail($itemId);

        // セッションからカートを取得（なければ空の配列）
        $cart = session()->get('cart', []);

        // すでにカートにある場合は数量を追加
        if (isset($cart[$itemId])) {
            $cart[$itemId]['quantity'] += $quantity;
        } else {
            // カートに追加
            $cart[$itemId] = [
                'id' => $item->id,
                'name' => $item->name,
                'price' => $item->price,
                'image' => $item->product_image,
                'quantity' => $quantity,
            ];
        }

        // カートをセッションに保存
        session(['cart' => $cart]);

        return redirect()->back()->with('success', 'カートに追加しました。');
    }

    public function index()
    {
        $userId = Auth::id();
        $allExhibitions = Exhibition::query();  // すべての商品を取得

        // ログインしている場合のみ、自分の出品を除外
        if ($userId) {
            $allExhibitions = $allExhibitions->where('user_id', '!=', $userId);
        }

        $allExhibitions = $allExhibitions->get();

        // お気に入り商品の取得（ログインしている場合のみ）
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
        $validated = $request->validated();

        // 画像処理
        $path = $request->hasFile('product_image')
            ? $request->file('product_image')->store('products', 'public')
            : 'products/default.jpg';

        // カテゴリーをJSONとして保存（ここがポイント）
        $categories = json_encode($validated['category']);

        $data = [
            'name' => $validated['name'],
            'brand' => $validated['brand'] ?? null,
            'detail' => $validated['detail'],
            'category' => $categories, // ← JSON文字列
            'condition' => $validated['condition'],
            'price' => $validated['price'],
            'user_id' => auth()->id(),
            'product_image' => $path,
        ];

        Exhibition::create($data);

        return redirect()->route('index')->with('success', '商品を出品しました！');
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
        // 認証ユーザー前提の場合
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
            Log::error('Purchase error: ' . $e->getMessage());
            return redirect()->back()->with('error', '購入処理中にエラーが発生しました。');
        }
    }

    public function purchases($exhibition_id)
    {
        $exhibition = Exhibition::findOrFail($exhibition_id);
        $quantity = 1;
        $user = auth()->user();

        $address = $user->address;

        return view('purchase', compact('exhibition', 'quantity', 'address'));
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use App\Models\Exhibition;
use App\Models\Purchase;
use Exception;

class StripeController extends Controller
{
    public function createCheckoutSession(Request $request)
    {
        try {
            // Stripeの設定
            $stripeSecret = config('services.stripe.secret');
            if (empty($stripeSecret)) {
                throw new Exception('Stripe secret key is not configured');
            }

            // Stripeの初期化
            \Stripe\Stripe::setApiKey($stripeSecret);

            // 商品情報の取得
            $exhibition = Exhibition::findOrFail($request->exhibition_id);

            // チェックアウトセッションの作成
            $session = \Stripe\Checkout\Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'jpy',
                        'product_data' => [
                            'name' => $exhibition->name,
                        ],
                        'unit_amount' => $exhibition->price,
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => route('purchase.success'),
                'cancel_url' => route('purchase.cancel'),
            ]);

            return response()->json(['id' => $session->id]);
        } catch (Exception $e) {
            return response()->json([
                'error' => '決済処理の準備中にエラーが発生しました。',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function success(Request $request)
    {
        // 購入完了時の処理
        return view('purchase.success');
    }

    public function cancel()
    {
        // 購入キャンセル時の処理
        return view('purchase.cancel');
    }
}

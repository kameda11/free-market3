<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Stripe\Stripe;
use App\Models\Exhibition;
use App\Models\Purchase;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class StripeController extends Controller
{
    public function createCheckoutSession(Request $request)
    {
        try {
            Log::info('Starting checkout session creation');

            // Stripeの設定
            $stripeSecret = config('services.stripe.secret');
            if (empty($stripeSecret)) {
                Log::error('Stripe secret key is not configured');
                throw new Exception('Stripe secret key is not configured');
            }

            // Stripeの初期化
            \Stripe\Stripe::setApiKey($stripeSecret);

            // 商品情報の取得
            $exhibition = Exhibition::findOrFail($request->input('exhibition_id'));
            Log::info('Exhibition found', ['id' => $exhibition->id]);

            if ($exhibition->sold) {
                Log::warning('Attempted to purchase sold item', ['exhibition_id' => $exhibition->id]);
                return response()->json([
                    'error' => 'この商品はすでに売却済みです。'
                ], 400);
            }

            // チェックアウトセッションの作成
            Log::info('Creating Stripe checkout session');
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
                'success_url' => url('/stripe/success?session_id={CHECKOUT_SESSION_ID}'),
                'metadata' => [
                    'exhibition_id' => $exhibition->id,
                    'user_id' => Auth::id(),
                ],
            ]);
            Log::info('Stripe session created', ['session_id' => $session->id]);

            return response()->json(['id' => $session->id]);
        } catch (Exception $e) {
            Log::error('Stripe session creation error: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'error' => '決済処理の準備中にエラーが発生しました。',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function success(Request $request)
    {
        try {
            Log::info('Starting success handler');
            $sessionId = $request->get('session_id');
            if (!$sessionId) {
                Log::warning('No session ID provided');
                return redirect()->route('index')->with('error', '決済セッションが見つかりません。');
            }

            // Stripeの設定
            $stripeSecret = config('services.stripe.secret');
            \Stripe\Stripe::setApiKey($stripeSecret);

            // セッション情報の取得
            Log::info('Retrieving Stripe session', ['session_id' => $sessionId]);
            $session = \Stripe\Checkout\Session::retrieve($sessionId);
            Log::info('Session retrieved', ['payment_status' => $session->payment_status]);

            if ($session->payment_status === 'paid') {
                // 商品を売却済みに更新
                $exhibition = Exhibition::find($session->metadata->exhibition_id);
                if ($exhibition) {
                    Log::info('Updating exhibition status', ['exhibition_id' => $exhibition->id]);
                    // トランザクション開始
                    DB::beginTransaction();
                    try {
                        // 商品を売却済みに更新（sold = 1）
                        $exhibition->sold = 1;
                        $exhibition->save();
                        Log::info('Exhibition marked as sold (sold = 1)');

                        // 購入情報の保存
                        Purchase::create([
                            'user_id' => $session->metadata->user_id,
                            'exhibition_id' => $session->metadata->exhibition_id,
                            'amount' => $session->amount_total,
                            'payment_method' => '2', // カード支払い
                        ]);
                        Log::info('Purchase record created');

                        DB::commit();
                        Log::info('Transaction committed successfully');
                        return redirect()->route('index')
                            ->with('success', '購入が完了しました！');
                    } catch (Exception $e) {
                        DB::rollBack();
                        Log::error('Purchase completion error: ' . $e->getMessage(), [
                            'exception' => $e,
                            'trace' => $e->getTraceAsString()
                        ]);
                        return redirect()->route('index')->with('error', '購入処理中にエラーが発生しました。');
                    }
                }
            }

            Log::warning('Payment not completed', ['session_id' => $sessionId]);
            return redirect()->route('index')->with('error', '決済が完了していません。');
        } catch (Exception $e) {
            Log::error('Stripe payment error: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->route('index')->with('error', '決済の確認中にエラーが発生しました。');
        }
    }
}

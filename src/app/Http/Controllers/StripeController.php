<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Stripe\Stripe;
use App\Models\Exhibition;
use App\Models\Purchase;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StripeController extends Controller
{
    public function createCheckoutSession(Request $request)
    {
        try {
            $stripeSecret = config('services.stripe.secret');
            if (empty($stripeSecret)) {
                throw new Exception('Stripe secret key is not configured');
            }

            \Stripe\Stripe::setApiKey($stripeSecret);
            $exhibition = Exhibition::findOrFail($request->input('exhibition_id'));

            if ($exhibition->sold) {
                return response()->json(['error' => 'この商品はすでに売却済みです。'], 400);
            }

            $session = \Stripe\Checkout\Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'jpy',
                        'product_data' => ['name' => $exhibition->name],
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
        try {
            $sessionId = $request->get('session_id');
            if (!$sessionId) {
                return redirect()->route('index')->with('error', '決済セッションが見つかりません。');
            }

            \Stripe\Stripe::setApiKey(config('services.stripe.secret'));
            $session = \Stripe\Checkout\Session::retrieve($sessionId);

            if ($session->payment_status !== 'paid') {
                return redirect()->route('index')->with('error', '決済が完了していません。');
            }

            $exhibition = Exhibition::find($session->metadata->exhibition_id);
            if (!$exhibition) {
                return redirect()->route('index')->with('error', '商品情報が見つかりません。');
            }

            DB::beginTransaction();
            try {
                $exhibition->update(['sold' => 1]);

                Purchase::create([
                    'user_id' => $session->metadata->user_id,
                    'exhibition_id' => $session->metadata->exhibition_id,
                    'amount' => $session->amount_total,
                    'payment_method' => '2',
                ]);

                DB::commit();
                return redirect()->route('index')->with('success', '購入が完了しました！');
            } catch (Exception $e) {
                DB::rollBack();
                return redirect()->route('index')->with('error', '購入処理中にエラーが発生しました。');
            }
        } catch (Exception $e) {
            return redirect()->route('index')->with('error', '決済の確認中にエラーが発生しました。');
        }
    }
}

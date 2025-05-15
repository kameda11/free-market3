@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/purchase.css') }}">
@endsection

@section('content')
<div class="purchase-container">
    <div class="purchase-main">
        <div class="product-section">
            <h2 class="section-title">購入内容の確認</h2>
            <div class="product-details">
                <div class="product-image">
                    <img src="{{ asset('storage/' . $exhibition->product_image) }}" alt="商品画像">
                </div>
                <div class="product-info">
                    <h3>{{ $exhibition->name }}</h3>
                    <p class="product-price">￥{{ number_format($exhibition->price) }}</p>
                </div>
            </div>

            <div class="payment-section">
                <h3 class="section-title">支払い方法</h3>
                <div class="payment-method">
                    <select name="payment_method" class="payment-select" required>
                        <option value="" selected>選択してください</option>
                        <option value="1">コンビニ払い</option>
                        <option value="2">カード払い</option>
                    </select>
                </div>
            </div>

            <div class="address-section">
                <div class="section-header">
                    <h3 class="section-title">配送先</h3>
                    @if($address)
                    <a href="{{ route('purchase.address', ['item_id' => $exhibition->id]) }}" class="change-link">変更する</a>
                    @endif
                </div>
                @if($address)
                <p>〒 {{ $address->post_code }}</p>
                <p>{{ $address->address }}</p>
                @if($address->building)
                <p>{{ $address->building }}</p>
                @endif
                @else
                <p>住所が登録されていません。</p>
                <a href="{{ route('purchase.address', ['item_id' => $exhibition->id]) }}">住所を登録する</a>
                @endif
            </div>
        </div>
    </div>

    <div class="purchase-sidebar">
        <div class="price-summary">
            <table class="price-table">
                <tr>
                    <th>商品代金</th>
                    <td>&yen;{{ number_format($exhibition->price) }}</td>
                </tr>
                <tr>
                    <th>支払い方法</th>
                    <td>
                        <div class="selected-payment">
                            <span id="payment-method-display">コンビニ払い</span>
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <form action="{{ route('purchase.complete') }}" method="POST" id="purchase-form">
            @csrf
            <input type="hidden" name="exhibition_id" value="{{ $exhibition->id }}">
            <input type="hidden" name="quantity" value="{{ $quantity }}">
            @if($address)
            <input type="hidden" name="address_id" value="{{ $address->id }}">
            @endif
            <input type="hidden" name="payment_method" id="payment_method" value="1">
            <button type="submit" id="purchase-button">購入する</button>
        </form>
    </div>
</div>
@endsection

@section('js')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const paymentSelect = document.querySelector('.payment-select');
        const paymentMethodInput = document.getElementById('payment_method');
        const paymentMethodDisplay = document.getElementById('payment-method-display');

        // 初期状態ではコンビニ払いを選択
        paymentMethodInput.value = '1';

        paymentSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption.value !== '') {
                paymentMethodInput.value = this.value;
                paymentMethodDisplay.textContent = selectedOption.text;
            }
        });
    });
</script>
@endsection
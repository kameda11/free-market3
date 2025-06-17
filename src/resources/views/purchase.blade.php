@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/purchase.css') }}">
<script src="https://js.stripe.com/v3/"></script>
@endsection

@section('content')
@if(session('error'))
<div class="error-message">
    {{ session('error') }}
</div>
@endif
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
                    <select name="payment_method" id="payment_method" class="payment-select" required>
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
                <div class="address-details">
                    <p>〒{{ $address->post_code }}</p>
                    <p>{{ $address->address }}</p>
                    @if($address->building)
                    <p>{{ $address->building }}</p>
                    @endif
                </div>
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
        const customSelect = document.querySelector('.payment-select');
        const selectContainer = document.createElement('div');
        selectContainer.className = 'custom-select';

        const selectSelected = document.createElement('div');
        selectSelected.className = 'select-selected';
        selectSelected.textContent = '選択してください';

        const selectItems = document.createElement('div');
        selectItems.className = 'select-items select-hide';

        function updateOptionStyles() {
            const options = selectItems.querySelectorAll('div');
            options.forEach(option => {
                const originalText = option.textContent.replace('✓', '').replace('　', '');
                option.textContent = option.classList.contains('selected') ? '✓' + originalText : '　' + originalText;
            });
        }

        Array.from(customSelect.options).forEach(option => {
            if (option.value !== '') {
                const div = document.createElement('div');
                const originalText = option.text.replace('✓', '').replace('　', '');
                div.textContent = option.selected ? '✓' + originalText : '　' + originalText;
                if (option.selected) {
                    div.classList.add('selected');
                }
                div.addEventListener('click', function() {
                    selectItems.querySelectorAll('div').forEach(d => d.classList.remove('selected'));
                    this.classList.add('selected');
                    customSelect.value = option.value;
                    selectSelected.textContent = this.textContent;
                    selectItems.classList.add('select-hide');
                    customSelect.dispatchEvent(new Event('change'));
                    updateOptionStyles();
                });
                selectItems.appendChild(div);
            }
        });

        selectContainer.appendChild(selectSelected);
        selectContainer.appendChild(selectItems);
        customSelect.parentNode.insertBefore(selectContainer, customSelect);
        customSelect.style.display = 'none';

        selectSelected.addEventListener('click', function(e) {
            e.stopPropagation();
            selectItems.classList.toggle('select-hide');
            updateOptionStyles();
        });

        document.addEventListener('click', function() {
            selectItems.classList.add('select-hide');
        });

        const paymentMethodInput = document.getElementById('payment_method');
        const paymentMethodDisplay = document.getElementById('payment-method-display');
        const purchaseForm = document.getElementById('purchase-form');

        paymentMethodInput.addEventListener('change', function() {
            const selectedMethod = this.value;
            paymentMethodDisplay.textContent = selectedMethod === '2' ? 'カード払い' : 'コンビニ払い';
        });

        const stripeKey = "{{ config('services.stripe.key') }}";
        if (!stripeKey) {
            console.error('Stripe key is not configured');
            return;
        }
        const stripe = Stripe(stripeKey);

        purchaseForm.addEventListener('submit', async function(e) {
            e.preventDefault();

            if (paymentMethodInput.value === '2') {
                try {
                    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                    if (!token) {
                        throw new Error('CSRF token not found');
                    }

                    const response = await fetch('/stripe/create-checkout-session', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': token
                        },
                        body: JSON.stringify({
                            exhibition_id: document.querySelector('input[name="exhibition_id"]').value
                        })
                    });

                    const data = await response.json();

                    if (!response.ok) {
                        throw new Error(data.message || '決済処理の準備中にエラーが発生しました。');
                    }

                    if (!data.id) {
                        throw new Error('決済セッションの作成に失敗しました。');
                    }

                    const result = await stripe.redirectToCheckout({
                        sessionId: data.id
                    });

                    if (result.error) {
                        console.error('Stripe redirect error:', result.error);
                        alert(result.error.message);
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert(error.message || '決済処理中にエラーが発生しました。');
                }
            } else {
                this.submit();
            }
        });
    });
</script>
@endsection
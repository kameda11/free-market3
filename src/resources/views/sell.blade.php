@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/sell.css') }}">
@endsection

@section('content')
<div class="sell-container">
    <h2 class="sell-title">商品を出品する</h2>

    <form action="{{ route('sell.store') }}" method="POST" enctype="multipart/form-data" class="sell-form">
        @csrf

        {{-- 商品画像 --}}
        <div class="form-group">
            <label for="product_image" class="form-label">
                <span class="label-text">商品画像</span>
            </label>
            <div class="image-upload-container">
                <div class="image-upload-button-container">
                    <label for="product_image" class="image-upload-button">
                        <div class="image-preview-area" id="imagePreviewArea">
                            <img id="imagePreview" class="image-preview" alt="画像プレビュー">
                            <span class="upload-text" id="uploadText">画像を選択する</span>
                        </div>
                    </label>
                    <input type="file" name="product_image" id="product_image" accept="image/*" class="image-input">
                    @error('product_image')
                    <p class="error-message">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        {{-- カテゴリー --}}
        <div class="form-group">
            <h3 class="section-title">商品の詳細</h3>
            <label class="form-label">カテゴリー</label>
            <div class="category-container">
                @php
                $categories = [
                'ファッション', '家電', 'インテリア', 'レディース', 'メンズ',
                'コスメ', '本', 'ゲーム', 'スポーツ', 'キッチン',
                'ハンドメイド', 'アクセサリー', 'おもちゃ', 'ベビー・キッズ'
                ];
                @endphp

                @foreach ($categories as $category)
                <label class="category-chip">
                    <input type="checkbox" name="category[]" value="{{ $category }}" class="category-input"
                        {{ is_array(old('category')) && in_array($category, old('category')) ? 'checked' : '' }}>
                    <span class="category-text">{{ $category }}</span>
                </label>
                @endforeach
            </div>
            @error('category')
            <p class="error-message">{{ $message }}</p>
            @enderror
        </div>

        {{-- 商品の状態 --}}
        <div class="form-group">
            <label for="condition" class="form-label__category">商品の状態</label>
            <div class="select-wrapper">
                <select name="condition" id="condition" class="form-input">
                    <option value="" selected disabled hidden>選択してください</option>
                    <option value="used_like_new">良好</option>
                    <option value="used_good">目立った傷や汚れなし</option>
                    <option value="used_fair">やや傷や汚れあり</option>
                    <option value="used_poor">状態が悪い</option>
                </select>
            </div>
            @error('condition')
            <p class="error-message">{{ $message }}</p>
            @enderror
        </div>

        {{-- 商品名と説明 --}}
        <div class="form-group">
            <h3 class="section-title">商品名と説明</h3>
            <label for="name" class="form-label">商品名</label>
            <input type="text" name="name" id="name" class="form-input" value="{{ old('name') }}">
            @error('name')
            <p class="error-message">{{ $message }}</p>
            @enderror
        </div>

        {{-- ブランド名 --}}
        <div class="form-group">
            <label for="brand" class="form-label">ブランド名</label>
            <input type="text" name="brand" id="brand" class="form-input" value="{{ old('brand') }}">
            @error('brand')
            <p class="error-message">{{ $message }}</p>
            @enderror
        </div>

        {{-- 商品の説明 --}}
        <div class="form-group">
            <label for="detail" class="form-label">商品の説明</label>
            <textarea name="detail" id="detail" rows="5" class="form-input">{{ old('detail') }}</textarea>
            @error('detail')
            <p class="error-message">{{ $message }}</p>
            @enderror
        </div>

        {{-- 価格 --}}
        <div class="form-group">
            <label for="price" class="form-label">価格（円）</label>
            <div class="price-input-container">
                <span class="price-symbol">￥</span>
                <input type="number" name="price" id="price" class="form-input price-input" value="{{ old('price') }}" min="0" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
            </div>
            @error('price')
            <p class="error-message">{{ $message }}</p>
            @enderror
        </div>

        {{-- 出品ボタン --}}
        <div class="form-group">
            <button type="submit" class="submit-button">出品する</button>
        </div>
    </form>
</div>
@endsection

@section('js')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // カスタムセレクトボックスの実装
        const customSelect = document.querySelector('select[name="condition"]');
        const selectContainer = document.createElement('div');
        selectContainer.className = 'custom-select';

        // 選択表示用の要素を作成
        const selectSelected = document.createElement('div');
        selectSelected.className = 'select-selected';
        selectSelected.textContent = '選択してください';

        // ドロップダウン用の要素を作成
        const selectItems = document.createElement('div');
        selectItems.className = 'select-items select-hide';

        // オプションをコピー（空のオプションを除外）
        Array.from(customSelect.options).forEach(option => {
            if (option.value !== '') { // 空のオプションをスキップ
                const div = document.createElement('div');
                const originalText = option.text.replace('✔', '').replace('　', '');
                div.textContent = option.selected ? '✔' + originalText : '　' + originalText;
                if (option.selected) {
                    div.classList.add('selected');
                }
                div.addEventListener('click', function() {
                    // 以前の選択を解除
                    selectItems.querySelectorAll('div').forEach(d => d.classList.remove('selected'));
                    // 新しい選択を設定
                    this.classList.add('selected');
                    customSelect.value = option.value;
                    selectSelected.textContent = this.textContent;
                    selectItems.classList.add('select-hide');
                    // 元のselectのchangeイベントを発火
                    customSelect.dispatchEvent(new Event('change'));
                });
                selectItems.appendChild(div);
            }
        });

        // 要素を配置
        selectContainer.appendChild(selectSelected);
        selectContainer.appendChild(selectItems);
        customSelect.parentNode.insertBefore(selectContainer, customSelect);
        customSelect.style.display = 'none';

        // クリックイベントの設定
        selectSelected.addEventListener('click', function(e) {
            e.stopPropagation();
            selectItems.classList.toggle('select-hide');
            updateOptionStyles();
        });

        // 外側をクリックしたら閉じる
        document.addEventListener('click', function() {
            selectItems.classList.add('select-hide');
        });

        // 選択肢のスタイルを更新する関数
        function updateOptionStyles() {
            const options = customSelect.options;
            const selectItemsDivs = selectItems.querySelectorAll('div');

            for (let i = 0; i < options.length; i++) {
                const option = options[i];
                if (option.value !== '') { // 空のオプションをスキップ
                    const originalText = option.text.replace('✔', '').replace('　', '');
                    if (option.selected) {
                        option.text = '✔' + originalText;
                        selectItemsDivs[i - 1].textContent = '✔' + originalText; // i-1 because we skip the empty option
                        selectItemsDivs[i - 1].classList.add('selected');
                    } else {
                        option.text = '　' + originalText;
                        selectItemsDivs[i - 1].textContent = '　' + originalText;
                        selectItemsDivs[i - 1].classList.remove('selected');
                    }
                }
            }
        }

        // 画像プレビュー機能
        const imageInput = document.querySelector('.image-input');
        const imagePreview = document.querySelector('.image-preview');
        const uploadButton = document.querySelector('.image-upload-button');
        const uploadText = document.querySelector('.upload-text');

        imageInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    imagePreview.src = e.target.result;
                    imagePreview.style.display = 'block';
                    uploadText.style.display = 'none';
                }
                reader.readAsDataURL(file);
            }
        });

        uploadButton.addEventListener('click', function() {
            imageInput.click();
        });
    });
</script>
@endsection
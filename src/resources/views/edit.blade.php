@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/edit.css') }}">
@endsection

@section('content')
<div class="profile-edit">
    <h1 class="profile-edit__title">プロフィール編集</h1>

    <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data" class="profile-edit__form">
        @csrf
        @method('PUT')

        <div class="profile-edit__image-section">
            <div class="profile-edit__image-container">
                <div class="profile-edit__current-image">
                    @if($user->profile && $user->profile->profile_image)
                    <img src="{{ asset('storage/' . $user->profile->profile_image) }}" alt="プロフィール画像" id="preview-image">
                    @else
                    <img src="{{ asset('storage/images/profile.png') }}" alt="プロフィール画像" id="preview-image">
                    @endif
                </div>
                <div class="profile-edit__image-upload">
                    <label for="profile_image" class="profile-edit__image-label">画像を選択する</label>
                    <input type="file" name="profile_image" id="profile_image" class="profile-edit__image-input" accept="image/*" onchange="previewImage(this)">
                </div>
            </div>
        </div>

        <div class="profile-edit__field">
            <label for="name" class="profile-edit__label">ユーザー名</label>
            <input type="text" name="name" id="name" class="profile-edit__input" value="{{ old('name', $user->name) }}" required>
            @error('name')
            <span class="profile-edit__error">{{ $message }}</span>
            @enderror
        </div>

        <div class="profile-edit__field">
            <label for="post_code" class="profile-edit__label">郵便番号</label>
            <input type="text" name="post_code" id="post_code" class="profile-edit__input" value="{{ old('post_code', $address->post_code ?? '') }}" required>
            @error('post_code')
            <span class="profile-edit__error">{{ $message }}</span>
            @enderror
        </div>

        <div class="profile-edit__field">
            <label for="address" class="profile-edit__label">住所</label>
            <input type="text" name="address" id="address" class="profile-edit__input" value="{{ old('address', $address->address ?? '') }}" required>
            @error('address')
            <span class="profile-edit__error">{{ $message }}</span>
            @enderror
        </div>

        <div class="profile-edit__field">
            <label for="building" class="profile-edit__label">建物名</label>
            <input type="text" name="building" id="building" class="profile-edit__input" value="{{ old('building', $address->building ?? '') }}">
            @error('building')
            <span class="profile-edit__error">{{ $message }}</span>
            @enderror
        </div>

        <button type="submit" class="profile-edit__submit">更新する</button>
    </form>
</div>

<script>
    function previewImage(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('preview-image').src = e.target.result;
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
</script>
@endsection
@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/address.css') }}">
@endsection

@section('content')
<div class="address-edit">
    <h2>住所の変更</h2>

    <form action="{{ route('address.update') }}" method="POST">
        @csrf
        @method('PUT')
        @if(isset($exhibition))
        <input type="hidden" name="item_id" value="{{ $exhibition->id }}">
        @endif

        <div class="form-group">
            <label for="post_code">郵便番号</label>
            <input type="text" name="post_code" id="post_code" value="{{ old('post_code', $address->post_code ?? '') }}" required>
            @error('post_code')
            <span class="error">{{ $message }}</span>
            @enderror
        </div>

        <div class="form-group">
            <label for="address">住所</label>
            <input type="text" name="address" id="address" value="{{ old('address', $address->address ?? '') }}" required>
            @error('address')
            <span class="error">{{ $message }}</span>
            @enderror
        </div>

        <div class="form-group">
            <label for="building">建物名</label>
            <input type="text" name="building" id="building" value="{{ old('building', $address->building ?? '') }}">
            @error('building')
            <span class="error">{{ $message }}</span>
            @enderror
        </div>

        <div class="form-group">
            <button type="submit" class="update-button">更新する</button>
        </div>
    </form>
</div>
@endsection
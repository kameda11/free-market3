@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/address.css') }}">
@endsection

@section('content')
<div class="address-edit">
    <h2>住所の変更</h2>

    <form action="{{ route('address.update') }}" method="POST">
        @csrf
        <input type="hidden" name="item_id" value="{{ $exhibition->id }}">
        <label>郵便番号<br>
            <input type="text" name="post_code" value="{{ old('post_code', $address->post_code ?? '') }}" required></label><br>
        <label>住所<br>
            <input type="text" name="address" value="{{ old('address', $address->address ?? '') }}" required></label><br>
        <label>建物名<br>
            <input type="text" name="building" value="{{ old('building', $address->building ?? '') }}"></label><br>

        <button type="submit">更新する</button>
    </form>
</div>
@endsection
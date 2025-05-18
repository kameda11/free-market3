@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/index.css') }}">
@endsection

@section('content')
<div class="filter-buttons">
    <a class="tab-button active" data-target="all">おすすめ</a>
    <a class="tab-button" data-target="favorites">マイリスト</a>
</div>

<div id="all" class="content-section active">
    @foreach($allExhibitions as $exhibition)
    <a href="{{ route('detail', $exhibition->id) }}" class="card__button card__button--compact">
        <div class="l-wrapper">
            <article class="card">
                <figure class="card__thumbnail">
                    <img src="{{ asset('storage/' . $exhibition->product_image) }}" alt="image" class="card__image">
                    @if($exhibition->sold)
                    <span class="sold-label">Sold</span>
                    @endif
                </figure>
                <h3 class="card__title">{{$exhibition->name}}</h3>
            </article>
        </div>
    </a>
    @endforeach
</div>

<div id="favorites" class="content-section">
    @forelse($favoriteExhibitions as $exhibition)
    <a href="{{ route('detail', $exhibition->id) }}" class="card__button card__button--compact">
        <div class="l-wrapper">
            <article class="card">
                <figure class="card__thumbnail">
                    <img src="{{ asset('storage/' . $exhibition->product_image) }}" alt="image" class="card__image">
                    @if($exhibition->sold)
                    <span class="sold-label">Sold</span>
                    @endif
                </figure>
                <h3 class="card__title">{{$exhibition->name}}</h3>
            </article>
        </div>
    </a>
    @empty
    <div class="no-favorites-message" style="text-align: center; padding: 40px; font-size: 18px; color: #777;">
        お気に入り登録している商品はありません。
    </div>
    @endforelse
</div>
@endsection

@section('js')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const buttons = document.querySelectorAll('.tab-button');
        const sections = document.querySelectorAll('.content-section');

        buttons.forEach(button => {
            button.addEventListener('click', function() {
                // Remove active class from all buttons and sections
                buttons.forEach(btn => btn.classList.remove('active'));
                sections.forEach(section => section.classList.remove('active'));

                // Add active to clicked button and corresponding section
                this.classList.add('active');
                const targetId = this.getAttribute('data-target');
                document.getElementById(targetId).classList.add('active');

                // Store active tab in session
                fetch('{{ route("store.tab") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        tab: targetId
                    })
                });
            });
        });
    });
</script>
@endsection
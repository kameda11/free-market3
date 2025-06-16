<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>coachtech</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}">
    <link rel="stylesheet" href="{{ asset('css/common.css') }}">
    @yield('css')
</head>

<body>
    <header class="header">
        <a class="header__logo" href="/">
            <img src="{{ asset('storage/logo.svg') }}">
        </a>
        @if (!Request::is('login') && !Request::is('register'))
        <form action="{{ route('search') }}" method="GET" class="header__search">
            <input type="text" name="query" placeholder="なにをお探しですか？" value="{{ request('query') }}" class="header__search-input">
        </form>
        <nav class="header__nav">
            <ul class="header__menu">
                @auth
                <li>
                    <a href="{{ route('logout') }}"
                        onclick="event.preventDefault(); document.getElementById('logout-form').submit();">ログアウト</a>
                </li>
                <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                    @csrf
                </form>
                <li><a href="{{ route('mypage') }}">マイページ</a></li>
                <li><a href="{{ route('sell') }}">出品</a></li>
                @endauth

                @guest
                <li><a href="{{ route('login') }}">ログイン</a></li>
                <li><a href="{{ route('login') }}">マイページ</a></li>
                <li><a href="{{ route('login') }}">出品</a></li>
                @endguest
            </ul>
        </nav>
        @endif
    </header>
    <div class="content">
        @yield('content')
        @yield('js')
    </div>
</body>

</html>
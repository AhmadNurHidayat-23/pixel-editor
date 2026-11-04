<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="referrer" content="no-referrer">
<title>Pixel Editor — Studio pixel art</title>
<meta name="description" content="Studio sederhana untuk menggambar pixel art, menyimpan karya, dan berkolaborasi.">
@vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body>
<div class="shell">
<header>
<a class="brand" href="{{ route('editor') }}">
<span class="logo-mark" aria-hidden="true">▦</span> Pixel Editor<span class="edition"> / 01</span>
</a>
<nav aria-label="Navigasi utama">
<a href="{{ route('editor') }}" @class(['selected'=>request()->routeIs('editor')])>Editor</a>
<a href="{{ route('artworks.gallery') }}" @class(['selected'=>request()->routeIs('artworks.gallery')])>Karya tersimpan</a>
</nav>
@yield('header-action')
</header>
<main>
@if(session('status'))<p class="notice" role="status">{{ session('status') }}</p>@endif
@yield('content')
</main>
<footer>
<span>Pixel Editor / Studio digital</span>
<span>Ruang kecil. Kemungkinan tak terbatas.</span>
<span>16 × 16 / 32 × 32</span>
</footer>
</div>
<dialog id="confirmation" aria-labelledby="confirm-title">
<h2 id="confirm-title">Konfirmasi perubahan</h2>
<p id="confirm-message">
</p>
<div class="dialog-actions">
<button id="confirm-cancel" class="button outline">Batal</button>
<button id="confirm-accept" class="button">Lanjutkan</button>
</div>
</dialog>
</body>
</html>

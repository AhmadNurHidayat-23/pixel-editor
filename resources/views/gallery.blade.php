@extends('layouts.app')
@section('header-action')<a class="button" href="{{ route('editor') }}">Karya baru <span>+</span>
</a>@endsection
@section('content')
<section class="hero">
<p class="eyebrow">02 / KOLEKSI PRIBADI</p>
<div class="hero-line">
<h1>Karya tersimpan.</h1>
<p>Ide kecil yang sudah menemukan bentuknya.</p>
</div>
</section>
<div class="gallery-intro">
<span>{{ $artworks->total() }} karya</span>
<p>Koleksi terhubung dengan sesi browser ini. Simpan tautan edit untuk akses dari perangkat lain.</p>
</div>
<section class="gallery" aria-label="Daftar karya">
@forelse($artworks as $artwork)<article class="artwork">
<div class="art-preview">
<canvas width="256" height="256" data-preview="{{ json_encode($artwork->only(['size','pixels'])) }}" aria-label="Pratinjau {{ $artwork->name }}">
</canvas>
</div>
<div class="artwork-meta">
<h2>{{ $artwork->name }}</h2>
<span>{{ $artwork->size }} × {{ $artwork->size }} / {{ $artwork->updated_at->timezone('Asia/Jakarta')->format('d.m.Y') }}</span>
</div>
<div class="artwork-actions">
<a href="{{ route('artworks.edit',$artwork->id) }}">Buka ↗</a>
<button class="text-button" data-copy="{{ route('view.show',$artwork->view_token) }}">Salin tautan</button>
<form action="{{ route('artworks.destroy',$artwork->id) }}" method="POST" data-confirm="Hapus karya ini? Tautan berbagi juga akan berhenti berfungsi.">@csrf @method('DELETE')<button class="text-button">Hapus</button>
</form>
</div>
</article>
@empty<div class="empty">
<span aria-hidden="true">▦</span>
<h2>Ruang untuk karya pertama.</h2>
<p>Gambar beberapa pixel, beri nama, lalu simpan di sini.</p>
<a href="{{ route('editor') }}">Buka editor →</a>
</div>@endforelse
</section>
@if($artworks->hasPages())<nav class="pagination" aria-label="Halaman koleksi">@if($artworks->previousPageUrl())<a href="{{ $artworks->previousPageUrl() }}">← Sebelumnya</a>@endif<span>{{ $artworks->currentPage() }} / {{ $artworks->lastPage() }}</span>@if($artworks->nextPageUrl())<a href="{{ $artworks->nextPageUrl() }}">Berikutnya →</a>@endif</nav>@endif
<p id="copy-status" role="status" aria-live="polite">
</p>
@endsection

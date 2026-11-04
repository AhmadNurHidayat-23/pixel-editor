@extends('layouts.app')
@section('header-action')
@if($config['editable'])<button class="button save-button" id="save">Simpan <span>↗</span>
</button>@else<span class="access-label">Hanya lihat</span>@endif
@endsection
@section('content')
<section class="hero">
<p class="eyebrow">01 / STUDIO PIXEL ART</p>
<div class="hero-line">
<h1>Pixel Editor.</h1>
<p lang="en">Create pixel art, one square at a time.</p>
</div>
</section>
<section id="editor" data-config="{{ json_encode($config) }}" data-store-url="{{ route('artworks.store') }}">
<div class="workspace-heading">
<div class="work-name">
<label class="sr-only" for="artwork-name">Nama karya</label>
<input id="artwork-name" maxlength="100" value="{{ $config['name'] }}" @readonly(!$config['editable'])>
<span id="sync-status" role="status" aria-live="polite">{{ $config['saved'] ? 'Karya tersimpan' : 'Belum disimpan' }}</span>
</div>
<div class="canvas-settings">
<label for="grid-size">Ukuran grid</label>
<select id="grid-size" @disabled(!$config['editable'])>
<option value="16" @selected($config['size']===16)>16 × 16</option>
<option value="32" @selected($config['size']===32)>32 × 32</option>
</select>
</div>
</div>
<div class="workspace">
<aside class="tools" aria-label="Peralatan editor">
<p class="eyebrow">PERALATAN</p>
<div class="tool-list" role="group" aria-label="Pilih alat">
<button class="tool active" data-tool="pencil" aria-pressed="true" @disabled(!$config['editable'])>
<span aria-hidden="true">↗</span> Pensil <kbd>P</kbd>
</button>
<button class="tool" data-tool="eraser" aria-pressed="false" @disabled(!$config['editable'])>
<span aria-hidden="true">◇</span> Penghapus <kbd>E</kbd>
</button>
<button class="tool" data-tool="fill" aria-pressed="false" @disabled(!$config['editable'])>
<span aria-hidden="true">▨</span> Isi area <kbd>F</kbd>
</button>
</div>
<div class="color-controls">
<p class="eyebrow">WARNA GAMBAR</p>
<div class="picker-row">
<label for="color-picker">Warna aktif</label>
<input type="color" id="color-picker" value="#214db3" @disabled(!$config['editable'])>
</div>
<output id="color-value" for="color-picker">#214DB3</output>
<div class="palette" aria-label="Palet warna">
</div>
</div>
<div class="history-tools">
<button id="undo" disabled>↶ <span>Urungkan</span>
</button>
<button id="redo" disabled>↷ <span>Ulangi</span>
</button>
</div>
<button id="clear" class="text-button" @disabled(!$config['editable'])>Bersihkan kanvas →</button>
<p class="tool-tip">Klik atau sentuh untuk menggambar. Seret untuk membuat garis.</p>
</aside>
<div class="canvas-area">
<div class="canvas-topline">
<span id="canvas-label">KANVAS / {{ $config['size'] }} × {{ $config['size'] }}</span>
<label>
<input type="checkbox" id="show-grid" checked> Tampilkan grid</label>
</div>
<div class="canvas-frame">
<canvas id="canvas" width="512" height="512" tabindex="0" aria-label="Kanvas pixel art. Gunakan tombol panah untuk memilih pixel, spasi untuk menggambar.">
</canvas>
</div>
<div class="canvas-bottomline">
<span id="coordinates">X — / Y —</span>
<span>Latar transparan · PNG</span>
</div>
</div>
<aside class="output-tools">
<p class="eyebrow">PRATINJAU</p>
<canvas id="preview" width="128" height="128" aria-label="Pratinjau karya">
</canvas>
<p class="preview-caption">Karya Anda, dalam skala kecil.</p>
<div class="export-actions">
<button class="button outline" id="download">Unduh PNG <span>↓</span>
</button>
<button class="text-button" id="share-view">Salin tautan lihat ↗</button>@if($config['editable'])<button class="text-button" id="share-edit">Salin tautan edit ↗</button>@endif</div>
<div class="collab-note">
<p class="eyebrow">{{ $config['editable'] ? 'BERKARYA BERSAMA' : 'TAUTAN LIHAT' }}</p>
<p>{{ $config['editable'] ? 'Simpan karya, lalu bagikan tautan edit untuk menggambar bersama. Perubahan disinkronkan otomatis.' : 'Nikmati karya ini atau unduh sebagai PNG. Tautan ini tidak memberikan hak mengedit.' }}</p>
<span class="sync-dot" aria-hidden="true">
</span>
<span>Sinkronisasi setiap 3 detik</span>
</div>
</aside>
</div>
</section>
<div class="editor-footnote">
<span>SATU PIXEL ADALAH AWAL.</span>
<p>Mulai dengan bentuk sederhana. Bangun sesuatu yang sepenuhnya milik Anda.</p>
<span>↑↓←→ Pilih pixel · Spasi Gambar</span>
</div>
@endsection

<?php

use App\Http\Controllers\ArtworkController as Editor;
use App\Http\Middleware\PrivateArtworkResponse;
use Illuminate\Support\Facades\Route;

Route::middleware([PrivateArtworkResponse::class, 'throttle:studio'])->group(function () {
    Route::get('/', [Editor::class, 'index'])->name('editor');
    Route::get('/gallery', [Editor::class, 'gallery'])->name('artworks.gallery');
    Route::post('/artworks', [Editor::class, 'store'])->middleware('throttle:saving')->name('artworks.store');
    Route::get('/artworks/{id}/edit', [Editor::class, 'show'])->whereNumber('id')->name('artworks.edit');
    Route::get('/artworks/{id}/state', [Editor::class, 'state'])->whereNumber('id')->name('artworks.state');
    Route::patch('/artworks/{id}', [Editor::class, 'update'])->middleware('throttle:saving')->whereNumber('id')->name('artworks.update');
    Route::delete('/artworks/{id}', [Editor::class, 'destroy'])->middleware('throttle:saving')->whereNumber('id')->name('artworks.destroy');
    Route::get('/view/{token}', [Editor::class, 'show'])->where('token', '[A-Za-z0-9]{64}')->name('view.show');
    Route::get('/view/{token}/state', [Editor::class, 'state'])->where('token', '[A-Za-z0-9]{64}')->name('view.state');
    Route::get('/edit/{token}', [Editor::class, 'show'])->where('token', '[A-Za-z0-9]{64}')->name('shared.show');
    Route::get('/edit/{token}/state', [Editor::class, 'state'])->where('token', '[A-Za-z0-9]{64}')->name('shared.state');
    Route::patch('/edit/{token}', [Editor::class, 'update'])->middleware('throttle:saving')->where('token', '[A-Za-z0-9]{64}')->name('shared.update');
});

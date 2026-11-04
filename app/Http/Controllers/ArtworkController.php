<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreArtworkRequest;
use App\Http\Requests\UpdateArtworkRequest;
use App\Models\Artwork;
use App\Services\ArtworkAccess;
use App\Services\ArtworkStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ArtworkController extends Controller
{
    public function __construct(private ArtworkAccess $access, private ArtworkStore $store) {}

    public function index(): View
    {
        return view('editor', ['config' => ['name' => 'Tanpa judul', 'size' => 16, 'pixels' => array_fill(0, 256, null), 'editable' => true, 'saved' => false, 'readUrl' => null, 'writeUrl' => null]]);
    }

    public function gallery(Request $request): View
    {
        return view('gallery', ['artworks' => Artwork::where('owner_hash', $this->access->owner($request))->latest('updated_at')->paginate(12)]);
    }

    public function show(Request $request): View
    {
        $artwork = $this->access->resolve($request);

        return view('editor', ['config' => $this->access->config($artwork, $this->access->editable($request), ! $request->route('token'))]);
    }

    public function state(Request $request): JsonResponse
    {
        return response()->json($this->access->resolve($request)->state());
    }

    public function store(StoreArtworkRequest $request): JsonResponse
    {
        $artwork = $this->store->create($request->validated(), $this->access->owner($request));

        return response()->json($this->access->config($artwork, true, true), 201);
    }

    public function update(UpdateArtworkRequest $request): JsonResponse
    {
        return response()->json($this->store->patch($this->access->resolve($request), $request->validated())->state());
    }

    public function destroy(Request $request): RedirectResponse
    {
        $this->access->resolve($request)->delete();

        return to_route('artworks.gallery')->with('status', 'Karya berhasil dihapus.');
    }
}

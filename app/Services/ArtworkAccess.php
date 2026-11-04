<?php

namespace App\Services;

use App\Models\Artwork;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ArtworkAccess
{
    public function owner(Request $request): string
    {
        if (! $request->session()->has('studio')) {
            $request->session()->put('studio', Str::random(64));
        }

        return hash('sha256', $request->session()->get('studio'));
    }

    public function resolve(Request $request): Artwork
    {
        if ($request->route('token')) {
            $token = $request->route('token');

            return str_starts_with($request->route()->getName(), 'shared.')
             ? Artwork::where('edit_hash', hash('sha256', $token))->firstOrFail()
             : Artwork::where('view_token', $token)->firstOrFail();
        }

        return Artwork::whereKey($request->route('id'))->where('owner_hash', $this->owner($request))->firstOrFail();
    }

    public function editable(Request $request): bool
    {
        return ! str_starts_with($request->route()->getName(), 'view.');
    }

    public function config(Artwork $artwork, bool $editable, bool $owner): array
    {
        $state = $artwork->state();
        $state['editable'] = $editable;
        $state['saved'] = true;
        $state['readUrl'] = $owner ? route('artworks.state', $artwork->id) : ($editable ? route('shared.state', $artwork->edit_token) : route('view.state', $artwork->view_token));
        $state['writeUrl'] = $owner ? route('artworks.update', $artwork->id) : ($editable ? route('shared.update', $artwork->edit_token) : null);
        $state['viewUrl'] = route('view.show', $artwork->view_token);
        $state['editUrl'] = $editable ? route('shared.show', $artwork->edit_token) : null;

        return $state;
    }
}

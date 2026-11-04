<?php

namespace App\Services;

use App\Models\Artwork;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ArtworkStore
{
    public function create(array $data, string $owner): Artwork
    {
        $token = Str::random(64);

        return Artwork::create([...$data, 'owner_hash' => $owner, 'view_token' => Str::random(64), 'edit_token' => $token, 'edit_hash' => hash('sha256', $token)]);
    }

    public function patch(Artwork $artwork, array $data): Artwork
    {
        DB::transaction(function () use ($artwork, $data): void {
            // Atomic JSON updates preserve pixels changed by another collaborator.
            foreach (array_chunk($data['changes'] ?? [], 50) as $chunk) {
                $pairs = [];
                $bindings = [];
                foreach ($chunk as $change) {
                    $pairs[] = '?, ?';
                    $bindings[] = '$['.$change['index'].']';
                    $bindings[] = $change['color'];
                }
                DB::update('UPDATE artworks SET pixels = json_set(pixels, '.implode(', ', $pairs).') WHERE id = ?', [...$bindings, $artwork->id]);
            }
            $update = ['version' => DB::raw('version + 1'), 'updated_at' => now()];
            if (isset($data['name'])) {
                $update['name'] = $data['name'];
            }
            Artwork::whereKey($artwork->id)->update($update);
        }, 5);

        return $artwork->refresh();
    }
}

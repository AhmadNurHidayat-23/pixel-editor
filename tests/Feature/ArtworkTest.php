<?php

namespace Tests\Feature;

use App\Models\Artwork;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class ArtworkTest extends TestCase
{
    use RefreshDatabase;

    private function ownerArtwork(): Artwork
    {
        return Artwork::factory()->create();
    }

    public function test_saves_artwork_with_distinct_tokens_and_encrypted_edit_secret(): void
    {
        $response = $this->postJson(route('artworks.store'), ['name' => 'Bunga', 'size' => 16, 'pixels' => array_fill(0, 256, null)])->assertCreated()->assertJsonPath('saved', true);
        $artwork = Artwork::firstOrFail();
        $this->assertNotSame($artwork->view_token, $artwork->edit_token);
        $this->assertSame(64, strlen($artwork->edit_token));
        $this->assertNotSame($artwork->edit_token, DB::table('artworks')->value('edit_token'));
        $this->get($response->json('readUrl'))->assertOk()->assertJsonPath('name', 'Bunga');
        $this->get(route('artworks.gallery'))->assertOk()->assertSee('Bunga');
    }

    public function test_owned_artwork_can_be_renamed_painted_and_deleted(): void
    {
        $artwork = $this->ownerArtwork();
        $this->withSession(['studio' => 'test-owner']);
        $this->get(route('artworks.edit', $artwork->id))->assertOk();
        $this->patchJson(route('artworks.update', $artwork->id), ['name' => 'Baru', 'changes' => [['index' => 0, 'color' => '#214db3']]])->assertOk()->assertJsonPath('pixels.0', '#214db3')->assertJsonPath('version', 2);
        $this->delete(route('artworks.destroy', $artwork->id))->assertRedirect(route('artworks.gallery'));
        $this->assertModelMissing($artwork);
    }

    public function test_other_session_cannot_access_modify_or_delete_owner_records(): void
    {
        $artwork = $this->ownerArtwork();
        $this->get(route('artworks.edit', $artwork->id))->assertNotFound();
        $this->get(route('artworks.state', $artwork->id))->assertNotFound();
        $this->patchJson(route('artworks.update', $artwork->id), ['name' => 'Tidak'])->assertNotFound();
        $this->delete(route('artworks.destroy', $artwork->id))->assertNotFound();
        $this->get(route('artworks.gallery'))->assertOk()->assertDontSee($artwork->name);
        $this->assertModelExists($artwork);
    }

    public function test_view_link_exposes_no_edit_token_and_cannot_write(): void
    {
        $artwork = $this->ownerArtwork();
        $this->get(route('view.show', $artwork->view_token))->assertOk()->assertSee('Hanya lihat')->assertDontSee($artwork->edit_token);
        $this->getJson(route('view.state', $artwork->view_token))->assertOk()->assertJsonMissingPath('edit_token')->assertJsonMissingPath('owner_hash')->assertJsonMissingPath('edit_hash');
        $this->patchJson(route('shared.update', $artwork->view_token), ['name' => 'Tidak'])->assertNotFound();
        $this->patchJson(route('view.show', $artwork->view_token), ['name' => 'Tidak'])->assertStatus(405);
        $this->assertSame($artwork->name, $artwork->fresh()->name);
    }

    public function test_edit_link_allows_collaboration_but_not_owner_gallery(): void
    {
        $artwork = $this->ownerArtwork();
        $this->get(route('shared.show', $artwork->edit_token))->assertOk()->assertSee('Salin tautan edit');
        $this->patchJson(route('shared.update', $artwork->edit_token), ['changes' => [['index' => 255, 'color' => '#ffffff']]])->assertOk()->assertJsonPath('pixels.255', '#ffffff');
        $this->get(route('artworks.gallery'))->assertOk()->assertDontSee($artwork->name);
    }

    public function test_disjoint_stale_client_patches_merge_and_same_pixel_last_save_wins(): void
    {
        $artwork = $this->ownerArtwork();
        $url = route('shared.update', $artwork->edit_token);
        $this->patchJson($url, ['changes' => [['index' => 0, 'color' => '#111111']]])->assertOk();
        $this->patchJson($url, ['changes' => [['index' => 1, 'color' => '#214db3']]])->assertOk()->assertJsonPath('pixels.0', '#111111')->assertJsonPath('pixels.1', '#214db3');
        $this->patchJson($url, ['changes' => [['index' => 0, 'color' => null]]])->assertOk()->assertJsonPath('pixels.0', null)->assertJsonPath('pixels.1', '#214db3')->assertJsonPath('version', 4);
    }

    public function test_large_fill_is_applied_across_sql_chunks(): void
    {
        $artwork = Artwork::factory()->create(['size' => 32, 'pixels' => array_fill(0, 1024, null)]);
        $changes = array_map(fn ($i) => ['index' => $i, 'color' => '#214db3'], range(0, 1023));
        $this->patchJson(route('shared.update', $artwork->edit_token), ['changes' => $changes])->assertOk()->assertJsonPath('pixels.1023', '#214db3');
        $this->assertSame(array_fill(0, 1024, '#214db3'), $artwork->fresh()->pixels);
    }

    #[TestWith([15, 256])]
    #[TestWith([16, 255])]
    #[TestWith([32, 256])]
    public function test_rejects_invalid_grid_dimensions(int $size, int $count): void
    {
        $this->postJson(route('artworks.store'), ['name' => 'Demo', 'size' => $size, 'pixels' => array_fill(0, $count, null)])->assertUnprocessable();
        $this->assertDatabaseCount('artworks', 0);
    }

    public function test_rejects_invalid_color_and_non_list_pixels(): void
    {
        $pixels = array_fill(0, 256, null);
        $pixels[0] = 'javascript:alert(1)';
        $this->postJson(route('artworks.store'), ['name' => 'Demo', 'size' => 16, 'pixels' => $pixels])->assertUnprocessable()->assertJsonValidationErrors('pixels.0');
        $this->assertDatabaseCount('artworks', 0);
    }

    #[TestWith([['changes' => [['index' => 256, 'color' => '#111111']]]])]
    #[TestWith([['changes' => [['index' => -1, 'color' => null]]]])]
    #[TestWith([['changes' => [['index' => 0, 'color' => 'bad']]]])]
    #[TestWith([['changes' => [['index' => 0]]]])]
    #[TestWith([['changes' => [['index' => 0, 'color' => null], ['index' => 0, 'color' => null]]]])]
    #[TestWith([['name' => 'Demo', 'size' => 32]])]
    #[TestWith([['name' => 'Demo', 'pixels' => []]])]
    public function test_rejects_invalid_changes_without_mutation(array $data): void
    {
        $artwork = $this->ownerArtwork();
        $this->patchJson(route('shared.update', $artwork->edit_token), $data)->assertUnprocessable();
        $this->assertSame(1, $artwork->fresh()->version);
        $this->assertSame(array_fill(0, 256, null), $artwork->fresh()->pixels);
    }

    public function test_unknown_or_deleted_share_links_return_404(): void
    {
        $artwork = $this->ownerArtwork();
        $token = $artwork->edit_token;
        $view = $artwork->view_token;
        $artwork->delete();
        $this->get(route('view.show', $view))->assertNotFound();
        $this->get(route('shared.show', $token))->assertNotFound();
        $this->get('/view/short')->assertNotFound();
    }

    public function test_escapes_artwork_names_and_sends_privacy_headers(): void
    {
        $artwork = Artwork::factory()->create(['name' => '<script>alert(1)</script>']);
        $this->withSession(['studio' => 'test-owner'])->get(route('artworks.gallery'))->assertOk()->assertSee('&lt;script&gt;', false)->assertDontSee('<script>alert(1)</script>', false)->assertHeader('Referrer-Policy', 'no-referrer')->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }

    public function test_save_rate_limit_is_enforced(): void
    {
        $artwork = $this->ownerArtwork();
        for ($i = 0; $i < 90; $i++) {
            $this->patchJson(route('shared.update', $artwork->edit_token), ['name' => 'Demo'])->assertOk();
        }
        $this->patchJson(route('shared.update', $artwork->edit_token), ['name' => 'Lain'])->assertTooManyRequests();
        $this->assertSame('Demo', $artwork->fresh()->name);
    }
}

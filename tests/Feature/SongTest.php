<?php

namespace Tests\Feature;

use App\Models\Song;
use App\Models\Tag;
use App\Models\User;
use App\Services\Pdf\SongbookPdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SongTest extends TestCase
{
    use RefreshDatabase;

    private function validSong(array $overrides = []): array
    {
        return [
            'title' => 'Ação de Graças',
            'artist' => 'Coral São José',
            'original_key' => 'G',
            'content' => "G          D/F#\nObrigado, Senhor, por tudo\n",
            'source' => 'manual',
            ...$overrides,
        ];
    }

    public function test_user_can_create_a_song_with_tags_and_new_tags(): void
    {
        $user = User::factory()->create();
        $tag = Tag::factory()->for($user)->create(['name' => 'Ofertório']);

        $response = $this->actingAs($user)->post('/songs', $this->validSong([
            'tags' => [$tag->id],
            'new_tags' => 'Natal, Mariana',
        ]));

        $song = Song::firstOrFail();
        $response->assertRedirect(route('songs.show', $song));
        $this->assertSame("G          D/F#\nObrigado, Senhor, por tudo\n", $song->defaultVersion()->content);
        $this->assertSame('Principal', $song->defaultVersion()->name);
        $this->assertEqualsCanonicalizing(['Ofertório', 'Natal', 'Mariana'], $song->tags->pluck('name')->all());
    }

    public function test_windows_line_breaks_are_normalized_but_spaces_preserved(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/songs', $this->validSong(['content' => "      C    G\r\n  Letra  com   espaços\r\n"]));

        $this->assertSame("      C    G\n  Letra  com   espaços\n", Song::firstOrFail()->defaultVersion()->content);
    }

    public function test_validation_rejects_invalid_key_and_youtube_url(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/songs', $this->validSong(['original_key' => 'H', 'youtube_url' => 'https://vimeo.com/123']))
            ->assertSessionHasErrors(['original_key', 'youtube_url']);
    }

    public function test_cannot_attach_tags_from_another_user(): void
    {
        $user = User::factory()->create();
        $foreignTag = Tag::factory()->create();

        $this->actingAs($user)->post('/songs', $this->validSong(['tags' => [$foreignTag->id]]))->assertSessionHasErrors('tags.0');
    }

    public function test_show_transposes_without_changing_the_original(): void
    {
        $user = User::factory()->create();
        $song = Song::factory()->for($user)->withVersion("C   G/B   Am\nSenhor, tende piedade\n", 'C')->create();

        $this->actingAs($user)->get(route('songs.show', [$song, 't' => 2]))
            ->assertOk()
            ->assertSee('D   A/C#  Bm')
            ->assertSee('Senhor, tende piedade');

        $this->assertSame("C   G/B   Am\nSenhor, tende piedade\n", $song->fresh()->defaultVersion()->content);
    }

    public function test_content_is_escaped(): void
    {
        $user = User::factory()->create();
        $song = Song::factory()->for($user)->withVersion("<img src=x onerror=alert(1)>\n")->create(['title' => '<script>alert(1)</script>']);

        $this->actingAs($user)->get(route('songs.show', $song))
            ->assertOk()
            ->assertDontSee('<img src=x onerror=alert(1)>', false)
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_search_and_tag_filters(): void
    {
        $user = User::factory()->create();
        $tagA = Tag::factory()->for($user)->create(['name' => 'Entrada']);
        $tagB = Tag::factory()->for($user)->create(['name' => 'Louvor']);
        $both = Song::factory()->for($user)->create(['title' => 'Vinde Cantar', 'artist' => 'X']);
        $both->tags()->attach([$tagA->id, $tagB->id]);
        $onlyA = Song::factory()->for($user)->create(['title' => 'Abri as Portas', 'artist' => 'Y']);
        $onlyA->tags()->attach($tagA);

        $this->actingAs($user)->get('/songs?q=vinde')->assertSee('Vinde Cantar')->assertDontSee('Abri as Portas');
        $this->actingAs($user)->get('/songs?tags[]='.$tagA->id)->assertSee('Vinde Cantar')->assertSee('Abri as Portas');
        $this->actingAs($user)->get('/songs?tags[]='.$tagA->id.'&tags[]='.$tagB->id)->assertSee('Vinde Cantar')->assertDontSee('Abri as Portas');
    }

    public function test_users_cannot_access_songs_of_others(): void
    {
        $song = Song::factory()->create();
        $intruder = User::factory()->create();

        $this->actingAs($intruder)->get(route('songs.show', $song))->assertForbidden();
        $this->actingAs($intruder)->put(route('songs.update', $song), $this->validSong())->assertForbidden();
        $this->actingAs($intruder)->delete(route('songs.destroy', $song))->assertForbidden();
        $this->actingAs($intruder)->get(route('songs.pdf', $song))->assertForbidden();
        $this->assertModelExists($song);
    }

    public function test_user_can_update_and_delete_a_song(): void
    {
        $user = User::factory()->create();
        $song = Song::factory()->for($user)->create();

        $this->actingAs($user)->put(route('songs.update', $song), $this->validSong(['title' => 'Novo título', 'source' => 'outro', 'source_url' => 'https://exemplo.com/cifra']))
            ->assertRedirect(route('songs.show', [$song, 'v' => $song->defaultVersion()->id]));
        $this->assertSame('Novo título', $song->fresh()->title);
        $this->assertSame('https://exemplo.com/cifra', $song->fresh()->source_url);

        $this->actingAs($user)->delete(route('songs.destroy', $song))->assertRedirect(route('songs.index'));
        $this->assertModelMissing($song);
    }

    public function test_single_song_pdf_download(): void
    {
        $user = User::factory()->create();
        $song = Song::factory()->for($user)->create(['title' => 'Glória a Deus']);

        $response = $this->actingAs($user)->get(route('songs.pdf', [$song, 't' => 3]));

        $response->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString('cifra-gloria-a-deus.pdf', $response->headers->get('content-disposition'));
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_import_chordpro_file_prefills_the_form(): void
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->createWithContent('santo.cho', "{title: Santo}\n{artist: Coral}\n{key: D}\n[D]Santo, [A]santo\n");

        $this->actingAs($user)->post(route('songs.import.file'), ['file' => $file])
            ->assertRedirect(route('songs.create'))
            ->assertSessionHasInput('title', 'Santo')
            ->assertSessionHasInput('artist', 'Coral')
            ->assertSessionHasInput('original_key', 'D')
            ->assertSessionHasInput('source', 'arquivo');

        $this->assertDatabaseCount('songs', 0);
    }

    public function test_import_pdf_reads_text_keeps_alignment_and_detects_key(): void
    {
        $user = User::factory()->create();
        $song = Song::factory()->for($user)->withVersion("G          D/F#\nObrigado, Senhor, ação de graças\n", 'G')->create(['title' => 'Ação de Graças']);
        $pdf = SongbookPdf::forVersion($song->defaultVersion(), 0, [
            'orientation' => 'portrait', 'font_size' => 11.0, 'margin' => 15, 'cover' => false,
            'toc' => false, 'page_break' => true, 'lyrics' => true, 'notes' => false,
        ])->output();
        $file = UploadedFile::fake()->createWithContent('acao-de-gracas.pdf', $pdf);

        $response = $this->actingAs($user)->post(route('songs.import.file'), ['file' => $file]);

        $response->assertRedirect(route('songs.create'))->assertSessionHasInput('original_key', 'G');
        $content = session('_old_input.content');
        $this->assertStringContainsString("G          D/F#\nObrigado, Senhor, ação de graças", $content);
        $this->assertDatabaseCount('songs', 1);
    }

    public function test_import_rejects_fake_pdf(): void
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->createWithContent('falso.pdf', 'isto não é um pdf');

        $this->actingAs($user)->post(route('songs.import.file'), ['file' => $file])->assertSessionHasErrors('file');
    }

    public function test_import_rejects_unsupported_files(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('songs.import.file'), ['file' => UploadedFile::fake()->create('virus.php', 1)])
            ->assertSessionHasErrors('file');
    }

    public function test_paste_import_uses_first_line_as_title(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('songs.import.paste'), ['content' => "Hino de Louvor\nC  G\nLetra\n"])
            ->assertSessionHasInput('title', 'Hino de Louvor')
            ->assertSessionHasInput('source', 'colagem');
    }

    public function test_paste_from_bookmarklet_keeps_source_and_valid_youtube_only(): void
    {
        $user = User::factory()->create();
        $payload = [
            'content' => "G  D\nLetra\n",
            'title' => 'Oração pela Família',
            'artist' => 'Padre Zezinho',
            'source' => 'cifraclub',
            'source_url' => 'https://www.cifraclub.com.br/padre-zezinho/oracao-pela-familia/',
        ];

        $this->actingAs($user)->post(route('songs.import.paste'), [...$payload, 'youtube_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ'])
            ->assertRedirect(route('songs.create'))
            ->assertSessionHasInput('source', 'cifraclub')
            ->assertSessionHasInput('source_url', $payload['source_url'])
            ->assertSessionHasInput('youtube_url', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ');

        $this->actingAs($user)->post(route('songs.import.paste'), [...$payload, 'youtube_url' => 'javascript:alert(1)'])
            ->assertSessionMissing('_old_input.youtube_url');

        $this->actingAs($user)->post(route('songs.import.paste'), [...$payload, 'source' => 'manual-hack'])
            ->assertSessionHasErrors('source');
    }

    public function test_import_page_offers_the_bookmarklet(): void
    {
        $this->actingAs(User::factory()->create())->get(route('songs.import'))
            ->assertOk()
            ->assertSee('Salvar no Cifra Ivo')
            ->assertSee('javascript:(()=&gt;', false);
    }

    public function test_external_import_is_disabled_without_configuration(): void
    {
        config(['services.cifraclub_api.url' => null]);
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('songs.import'))->assertSee('Desativada');
        $this->actingAs($user)->post(route('songs.import.external'), ['reference' => 'artista/musica'])
            ->assertSessionHasErrors(['reference' => 'A importação externa não está configurada.']);
    }

    public function test_external_import_maps_the_cifraclub_api_response(): void
    {
        config(['services.cifraclub_api.url' => 'http://cifraclub-api.local:3000']);
        Http::fake([
            'cifraclub-api.local:3000/artists/coral/songs/santo' => Http::response([
                'artist' => 'Coral', 'name' => 'Santo', 'youtube_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                'cifraclub_url' => 'https://www.cifraclub.com.br/coral/santo/', 'cifra' => ['C  G', 'Santo, santo'],
            ]),
        ]);
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('songs.import.external'), ['reference' => 'https://www.cifraclub.com.br/coral/santo/'])
            ->assertRedirect(route('songs.create'))
            ->assertSessionHasInput('title', 'Santo')
            ->assertSessionHasInput('content', "C  G\nSanto, santo\n")
            ->assertSessionHasInput('source', 'cifraclub-api')
            ->assertSessionHasInput('source_url', 'https://www.cifraclub.com.br/coral/santo/');
    }
}

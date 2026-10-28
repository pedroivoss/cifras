<?php

namespace Tests\Feature;

use App\Http\Controllers\PlaylistController;
use App\Models\Playlist;
use App\Models\PlaylistItem;
use App\Models\Song;
use App\Models\User;
use App\Services\Docx\SongbookDocx;
use App\Services\Pdf\SongbookPdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

class MassBookletTest extends TestCase
{
    use RefreshDatabase;

    private const OPTIONS = [
        'orientation' => 'portrait', 'font_size' => 11.0, 'margin' => 15, 'cover' => false,
        'toc' => false, 'page_break' => true, 'lyrics' => true, 'notes' => false,
    ];

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(Playlist::COVER_DISK);
    }

    public function test_text_items_are_added_at_the_end_and_edited(): void
    {
        $user = User::factory()->create();
        $playlist = Playlist::factory()->for($user)->create();
        PlaylistItem::factory()->for($playlist)->create(['song_id' => Song::factory()->for($user), 'position' => 1]);

        $this->actingAs($user)->get(route('playlists.texts.create', $playlist))->assertOk()->assertSee('Salmo Responsorial');
        $this->actingAs($user)->post(route('playlists.texts.store', $playlist), ['title' => 'Primeira Leitura (Ap 7,2-4)', 'body' => "Leitura…\n\n**- Graças a Deus.**"])
            ->assertRedirect(route('playlists.show', $playlist));

        $text = $playlist->items()->get()->last();
        $this->assertTrue($text->isText());
        $this->assertNull($text->song_id);
        $this->assertSame(2, $text->position);

        $this->actingAs($user)->put(route('playlist-items.content', $text), ['title' => 'Evangelho', 'body' => 'Naquele tempo…'])->assertRedirect();
        $this->assertSame('Evangelho', $text->fresh()->title);

        $this->actingAs($user)->post(route('playlists.texts.store', $playlist), ['title' => ''])->assertSessionHasErrors('title');
    }

    public function test_songs_are_numbered_and_texts_are_not(): void
    {
        $user = User::factory()->create();
        $playlist = Playlist::factory()->for($user)->create();
        PlaylistItem::factory()->for($playlist)->create(['song_id' => Song::factory()->for($user)->withVersion("C\nLetra\n", 'C'), 'position' => 1]);
        PlaylistItem::factory()->for($playlist)->text('Primeira Leitura', "Texto\n**- Graças a Deus.**")->create(['position' => 2]);
        PlaylistItem::factory()->for($playlist)->create(['song_id' => Song::factory()->for($user)->withVersion("G\nLetra\n", 'G'), 'position' => 3]);

        $entries = SongbookPdf::playlistEntries($playlist, self::OPTIONS);

        $this->assertSame(['song', 'text', 'song'], array_column($entries, 'kind'));
        $this->assertSame([1, null, 2], array_column($entries, 'number'));
        $this->assertSame('Texto<br><strong>- Graças a Deus.</strong>', strip_tags((string) $entries[1]['html'], '<br><strong>'));

        $this->actingAs($user)->get(route('playlists.sheets', $playlist))
            ->assertOk()
            ->assertSee('Primeira Leitura')
            ->assertSee('<strong>- Graças a Deus.</strong>', false)
            ->assertSee('2 músicas');

        $this->actingAs($user)->get(route('playlists.show', $playlist))->assertOk()->assertSee('Adicionar texto');
    }

    public function test_song_can_be_adjusted_only_in_one_playlist(): void
    {
        $user = User::factory()->create();
        $song = Song::factory()->for($user)->withVersion("A\nAleluia\n", 'A')->create();
        $item = PlaylistItem::factory()->create(['playlist_id' => Playlist::factory()->for($user), 'song_id' => $song, 'semitones' => 2]);
        $other = PlaylistItem::factory()->create(['playlist_id' => Playlist::factory()->for($user), 'song_id' => $song]);

        $this->actingAs($user)->get(route('playlist-items.edit', $item))->assertOk()->assertSee("A\nAleluia", false);
        $this->actingAs($user)->put(route('playlist-items.content', $item), ['content' => "A  E\nAleluia\n  Vinde a mim, todos vós\n"])->assertRedirect();

        $item->refresh();
        $this->assertTrue($item->hasCustomContent());
        $this->assertSame("A\nAleluia\n", $song->defaultVersion()->fresh()->content, 'A música da biblioteca não muda');
        $this->assertNull($other->fresh()->content);

        $entry = SongbookPdf::playlistEntries($item->playlist, self::OPTIONS)[0];
        $texts = collect($entry['blocks'])->flatten(1)->pluck('text')->all();
        $this->assertSame(['B  F#', 'Aleluia', '  Vinde a mim, todos vós'], $texts, 'Ajuste usa o tom da playlist e mantém espaços');

        $this->actingAs($user)->get(route('playlists.show', $item->playlist))->assertOk();
        $this->assertTrue(PlaylistController::itemPayload($item->fresh(['song.versions', 'version']))['custom']);

        $this->actingAs($user)->put(route('playlist-items.content', $item), ['restore' => 1])->assertRedirect();
        $this->assertNull($item->fresh()->content);

        $this->actingAs($user)->put(route('playlist-items.content', $item), ['content' => "A\nAleluia\n"]);
        $this->assertNull($item->fresh()->content, 'Igual ao original não guarda cópia');
    }

    public function test_cover_image_upload_access_replace_and_cleanup(): void
    {
        $user = User::factory()->create();
        $playlist = Playlist::factory()->for($user)->create(['name' => 'Missa']);

        $this->actingAs($user)->put(route('playlists.update', $playlist), [
            'name' => 'Missa', 'cover_title' => 'Todos os Santos – 2026', 'cover_quote' => 'Alegrai-vos e exultai',
            'cover_image' => UploadedFile::fake()->image('santos.jpg', 800, 1000),
        ])->assertRedirect();

        $playlist->refresh();
        $first = $playlist->cover_image;
        $this->assertTrue($playlist->hasCustomCover());
        Storage::disk(Playlist::COVER_DISK)->assertExists($first);

        $this->actingAs($user)->get(route('playlists.cover', $playlist))->assertOk();
        $this->actingAs(User::factory()->create())->get(route('playlists.cover', $playlist))->assertForbidden();
        $this->get(route('public.playlist.cover', $playlist->share_token))->assertNotFound();
        $playlist->update(['is_public' => true]);
        $this->get(route('public.playlist.cover', $playlist->share_token))->assertOk();

        $this->actingAs($user)->put(route('playlists.update', $playlist), ['name' => 'Missa', 'cover_image' => UploadedFile::fake()->image('nova.png', 400, 300)]);
        Storage::disk(Playlist::COVER_DISK)->assertMissing($first);
        $second = $playlist->fresh()->cover_image;
        $this->assertSame('Todos os Santos – 2026', $playlist->fresh()->cover_title);

        $this->actingAs($user)->put(route('playlists.update', $playlist), ['name' => 'Missa', 'cover_image' => UploadedFile::fake()->create('x.pdf', 10, 'application/pdf')])
            ->assertSessionHasErrors('cover_image');

        $this->actingAs($user)->put(route('playlists.update', $playlist), ['name' => 'Missa', 'cover_title' => 'Todos os Santos – 2026', 'remove_cover_image' => 1]);
        $this->assertNull($playlist->fresh()->cover_image);
        Storage::disk(Playlist::COVER_DISK)->assertMissing($second);
    }

    public function test_deleting_and_duplicating_keep_cover_files_consistent(): void
    {
        $user = User::factory()->create();
        $playlist = Playlist::factory()->for($user)->create(['cover_title' => 'Capa']);
        $this->actingAs($user)->put(route('playlists.update', $playlist), ['name' => 'Missa', 'cover_title' => 'Capa', 'cover_image' => UploadedFile::fake()->image('a.jpg')]);
        $song = Song::factory()->for($user)->withVersion("C\n", 'C')->create();
        PlaylistItem::factory()->for($playlist)->create(['song_id' => $song, 'position' => 1, 'capo' => 3, 'content' => "D\n"]);
        PlaylistItem::factory()->for($playlist)->text('Evangelho', 'Texto')->create(['position' => 2]);

        $copy = $playlist->fresh()->duplicate();

        $this->assertNotSame($playlist->fresh()->cover_image, $copy->cover_image);
        Storage::disk(Playlist::COVER_DISK)->assertExists($copy->cover_image);
        $this->assertSame(['song', 'text'], $copy->items()->pluck('kind')->all());
        $this->assertSame("D\n", $copy->items()->first()->content);
        $this->assertSame(3, $copy->items()->first()->capo);

        $original = $playlist->fresh()->cover_image;
        $this->actingAs($user)->delete(route('playlists.destroy', $playlist));
        Storage::disk(Playlist::COVER_DISK)->assertMissing($original);
        Storage::disk(Playlist::COVER_DISK)->assertExists($copy->cover_image);
    }

    public function test_pdf_and_docx_include_cover_and_texts(): void
    {
        $user = User::factory()->create();
        $playlist = Playlist::factory()->for($user)->create(['name' => 'Missa']);
        $this->actingAs($user)->put(route('playlists.update', $playlist), [
            'name' => 'Missa', 'cover_title' => 'Todos os Santos', 'cover_quote' => 'Alegrai-vos',
            'cover_image' => UploadedFile::fake()->image('capa.png', 600, 800),
        ]);
        PlaylistItem::factory()->for($playlist)->create(['song_id' => Song::factory()->for($user)->withVersion("C\nLetra\n", 'C'), 'position' => 1]);
        PlaylistItem::factory()->for($playlist)->text('Primeira Leitura', "Leitura do livro.\n\n**- Graças a Deus.**")->create(['position' => 2]);

        $this->actingAs($user)->get(route('playlists.pdf', $playlist))->assertOk()->assertHeader('content-type', 'application/pdf');

        $response = $this->actingAs($user)->get(route('playlists.docx', $playlist))->assertOk();
        $path = tempnam(sys_get_temp_dir(), 'docx-test');
        file_put_contents($path, $response->getContent());
        $zip = new ZipArchive;
        $zip->open($path);
        $document = $zip->getFromName('word/document.xml');
        $this->assertNotFalse($zip->getFromName('word/media/cover.png'), 'Capa vem marcada por padrão quando há capa personalizada');
        $this->assertStringContainsString('rIdCover', $zip->getFromName('word/_rels/document.xml.rels'));
        $zip->close();
        unlink($path);

        $this->assertStringContainsString('Todos os Santos', $document);
        $this->assertStringContainsString('<w:pStyle w:val="TextTitle"/>', $document);
        $this->assertMatchesRegularExpression('/<w:b\/><w:bCs\/>.*?<w:t xml:space="preserve">- Graças a Deus\.<\/w:t>/s', $document);

        $withoutCover = SongbookDocx::forPlaylist($playlist->fresh(), self::OPTIONS);
        $this->assertStringNotContainsString('word/media/cover.png', $withoutCover);
    }

    public function test_other_users_cannot_touch_texts_or_adjustments(): void
    {
        $item = PlaylistItem::factory()->create();
        $intruder = User::factory()->create();

        $this->actingAs($intruder)->get(route('playlist-items.edit', $item))->assertForbidden();
        $this->actingAs($intruder)->put(route('playlist-items.content', $item), ['content' => 'X'])->assertForbidden();
        $this->actingAs($intruder)->get(route('playlists.texts.create', $item->playlist_id))->assertForbidden();
        $this->actingAs($intruder)->post(route('playlists.texts.store', $item->playlist_id), ['title' => 'X'])->assertForbidden();
        $this->assertNull($item->fresh()->content);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Playlist;
use App\Models\PlaylistItem;
use App\Models\Song;
use App\Models\SongVersion;
use App\Models\User;
use App\Services\Docx\SongbookDocx;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use ZipArchive;

class DocxExportTest extends TestCase
{
    use RefreshDatabase;

    private const OPTIONS = [
        'orientation' => 'portrait', 'font_size' => 11.0, 'margin' => 15, 'cover' => false,
        'toc' => false, 'page_break' => true, 'lyrics' => true, 'notes' => false,
    ];

    public function test_playlist_docx_download_is_a_valid_word_package(): void
    {
        $user = User::factory()->create();
        $playlist = Playlist::factory()->for($user)->create(['name' => 'Missa de Ação de Graças']);
        PlaylistItem::factory()->for($playlist)->create(['song_id' => Song::factory()->for($user)->withVersion("C  G\nLetra\n", 'C'), 'position' => 1]);

        $response = $this->actingAs($user)->get(route('playlists.docx', $playlist));

        $response->assertOk()->assertHeader('content-type', SongbookDocx::CONTENT_TYPE);
        $this->assertStringContainsString('attachment; filename=repertorio-missa-de-acao-de-gracas.docx', $response->headers->get('content-disposition'));

        $parts = $this->parts($response->getContent());

        foreach (['[Content_Types].xml', '_rels/.rels', 'word/document.xml', 'word/styles.xml', 'word/settings.xml', 'word/footer1.xml', 'word/_rels/document.xml.rels', 'docProps/core.xml'] as $name) {
            $this->assertArrayHasKey($name, $parts);
            $this->assertTrue((new DOMDocument)->loadXML($parts[$name]), "{$name} não é XML válido");
        }

        $this->assertStringContainsString('<dc:title>Missa de Ação de Graças</dc:title>', $parts['docProps/core.xml']);
        $this->assertStringContainsString('NUMPAGES', $parts['word/footer1.xml']);
    }

    public function test_docx_follows_saved_order_keys_and_keeps_chord_alignment(): void
    {
        $user = User::factory()->create();
        $playlist = Playlist::factory()->for($user)->create();
        $first = Song::factory()->for($user)->withVersion("Am      E7\nLetra & <dois>\n", 'Am')->create(['title' => 'Segunda', 'artist' => 'Autor']);
        $second = Song::factory()->for($user)->withVersion("[Refrão]\nC  G\n", 'C')->create(['title' => 'Primeira', 'artist' => null]);
        PlaylistItem::factory()->for($playlist)->create(['song_id' => $first, 'position' => 1, 'semitones' => 2]);
        PlaylistItem::factory()->for($playlist)->create(['song_id' => $second, 'position' => 2]);

        $xpath = $this->documentXpath(SongbookDocx::forPlaylist($playlist, self::OPTIONS));

        $this->assertSame(['1. Segunda', '2. Primeira'], $this->texts($xpath, '//w:p[w:pPr/w:pStyle/@w:val="Heading1"]'));
        $this->assertSame(['Autor · Tom: Bm', 'Tom: C'], $this->texts($xpath, '//w:p[w:pPr/w:pStyle/@w:val="SongInfo"]'));
        $this->assertSame(['Bm      F#7', 'C  G'], $this->texts($xpath, '//w:r[w:rPr/w:rStyle/@w:val="Chord"]'));
        $this->assertSame(['Letra & <dois>'], array_values(array_filter(
            $this->texts($xpath, '//w:p[w:pPr/w:pStyle/@w:val="Sheet"][not(.//w:rStyle)]'),
            fn (string $t) => $t !== '',
        )));
        $this->assertSame(['Refrão'], $this->texts($xpath, '//w:p[w:pPr/w:pStyle/@w:val="SongSection"]'));
        $this->assertSame('preserve', $xpath->evaluate('string(//w:r[w:rPr/w:rStyle/@w:val="Chord"]/w:t/@xml:space)'));
        $this->assertSame(1.0, $xpath->evaluate('count(//w:pageBreakBefore)'), 'Só a segunda música começa em nova página');
    }

    public function test_docx_shows_version_capo_cover_toc_and_landscape(): void
    {
        $user = User::factory()->create();
        $playlist = Playlist::factory()->for($user)->create(['name' => 'Natal', 'description' => "Linha 1\nLinha 2"]);
        $song = Song::factory()->for($user)->withVersion("C\n", 'C')->create(['title' => 'Noite Feliz', 'artist' => null]);
        $simple = SongVersion::factory()->for($song)->create(['name' => 'Simplificada', 'content' => "G\n", 'original_key' => 'G', 'capo' => 2, 'position' => 2]);
        PlaylistItem::factory()->for($playlist)->create(['song_id' => $song, 'song_version_id' => $simple, 'notes' => 'Entrada']);

        $docx = SongbookDocx::forPlaylist($playlist, [...self::OPTIONS, 'cover' => true, 'toc' => true, 'notes' => true, 'orientation' => 'landscape', 'page_break' => false]);
        $xpath = $this->documentXpath($docx);

        $this->assertSame(['1. Noite Feliz (Simplificada)'], $this->texts($xpath, '//w:p[w:pPr/w:pStyle/@w:val="Heading1"]'));
        $this->assertSame(['Tom: G · Capo na 2ª casa (soa em A)'], $this->texts($xpath, '//w:p[w:pPr/w:pStyle/@w:val="SongInfo"]'));
        $this->assertSame(['Entrada'], $this->texts($xpath, '//w:p[w:pPr/w:pStyle/@w:val="SongNotes"]'));
        $this->assertSame(['1.', 'Noite Feliz', 'A (capo 2)'], $this->texts($xpath, '//w:tbl//w:p'));
        $this->assertContains('Natal', $this->texts($xpath, '//w:p'));
        $this->assertSame(1.0, $xpath->evaluate('count(//w:br)'), 'Quebra de linha da descrição preservada');
        $this->assertSame('landscape', $xpath->evaluate('string(//w:sectPr/w:pgSz/@w:orient)'));
        $this->assertSame('16838', $xpath->evaluate('string(//w:sectPr/w:pgSz/@w:w)'));
        $this->assertSame(1.0, $xpath->evaluate('count(//w:pageBreakBefore)'), 'Sumário em nova página; música segue sem quebra');
    }

    public function test_song_docx_uses_selected_version_and_key(): void
    {
        $user = User::factory()->create();
        $song = Song::factory()->for($user)->withVersion("C  G\n", 'C')->create(['title' => 'Aleluia']);
        $other = SongVersion::factory()->for($song)->create(['name' => 'Simplificada', 'content' => "D  A\n", 'original_key' => 'D', 'position' => 2]);

        $response = $this->actingAs($user)->get(route('songs.docx', [$song, 'v' => $other->id, 't' => 2]));

        $response->assertOk();
        $this->assertStringContainsString('filename=cifra-aleluia.docx', $response->headers->get('content-disposition'));

        $xpath = $this->documentXpath($response->getContent());
        $this->assertSame(['Aleluia (Simplificada)'], $this->texts($xpath, '//w:p[w:pPr/w:pStyle/@w:val="Heading1"]'));
        $this->assertSame(['E  B'], $this->texts($xpath, '//w:r[w:rPr/w:rStyle/@w:val="Chord"]'));
    }

    public function test_other_users_cannot_export_docx(): void
    {
        $item = PlaylistItem::factory()->create();
        $intruder = User::factory()->create();

        $this->actingAs($intruder)->get(route('playlists.docx', $item->playlist_id))->assertForbidden();
        $this->actingAs($intruder)->get(route('songs.docx', $item->song_id))->assertForbidden();
    }

    public function test_guests_are_sent_to_login(): void
    {
        $item = PlaylistItem::factory()->create();

        $this->get(route('playlists.docx', $item->playlist_id))->assertRedirect(route('login'));
    }

    /**
     * @return array<string, string>
     */
    private function parts(string $docx): array
    {
        $path = tempnam(sys_get_temp_dir(), 'docx-test');
        file_put_contents($path, $docx);
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($path));

        $parts = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            $parts[$name] = $zip->getFromIndex($i);
        }

        $zip->close();
        unlink($path);

        return $parts;
    }

    private function documentXpath(string $docx): DOMXPath
    {
        $dom = new DOMDocument;
        $this->assertTrue($dom->loadXML($this->parts($docx)['word/document.xml']));
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

        return $xpath;
    }

    /**
     * @return list<string>
     */
    private function texts(DOMXPath $xpath, string $query): array
    {
        $texts = [];

        foreach ($xpath->query($query) as $node) {
            $texts[] = implode('', array_map(fn ($t) => $t->textContent, iterator_to_array($xpath->query('.//w:t', $node))));
        }

        return $texts;
    }
}

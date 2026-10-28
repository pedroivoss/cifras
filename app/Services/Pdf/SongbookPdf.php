<?php

namespace App\Services\Pdf;

use App\Models\Playlist;
use App\Models\SongVersion;
use App\Services\Chords\ChordSheet;
use App\Services\Chords\Transposer;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdf;
use Illuminate\Http\Response;

/**
 * Gera o PDF de uma playlist (ou de uma música) no servidor com Dompdf, sem armazenar arquivo.
 * A ordem e os tons vêm exatamente dos itens salvos.
 */
final class SongbookPdf
{
    /** Largura média de um caractere da DejaVu Sans Mono, em "em". */
    private const MONO_ADVANCE = 0.602;

    private const MIN_FONT_SIZE = 6.5;

    private const PT_PER_MM = 2.8346;

    /**
     * @param  array{orientation: string, font_size: float, margin: int, cover: bool, toc: bool, page_break: bool, lyrics: bool, notes: bool}  $options
     */
    public static function forPlaylist(Playlist $playlist, array $options): DomPdf
    {
        return self::build($playlist->name, $playlist, self::playlistEntries($playlist, $options), $options);
    }

    /**
     * Entradas do PDF exatamente na ordem e nos tons salvos.
     *
     * @param  array{orientation: string, font_size: float, margin: int, cover: bool, toc: bool, page_break: bool, lyrics: bool, notes: bool}  $options
     * @return list<array<string, mixed>>
     */
    public static function playlistEntries(Playlist $playlist, array $options): array
    {
        $playlist->loadMissing(['items.song.versions', 'items.version']);

        return $playlist->items->values()->map(fn ($item, $index) => self::entry(
            $item->sheetVersion(),
            $item->semitones,
            $item->effectiveCapo(),
            $index + 1,
            $item->notes,
            $options,
        ))->all();
    }

    /**
     * @param  array{orientation: string, font_size: float, margin: int, cover: bool, toc: bool, page_break: bool, lyrics: bool, notes: bool}  $options
     */
    public static function forVersion(SongVersion $version, int $semitones, array $options): DomPdf
    {
        $options['cover'] = false;
        $options['toc'] = false;

        return self::build($version->song->title, null, [self::versionEntry($version, $semitones, $options)], $options);
    }

    /**
     * Entrada de uma música avulsa (sem número na lista), no capo da própria versão.
     *
     * @param  array{orientation: string, font_size: float, margin: int, cover: bool, toc: bool, page_break: bool, lyrics: bool, notes: bool}  $options
     * @return array<string, mixed>
     */
    public static function versionEntry(SongVersion $version, int $semitones, array $options): array
    {
        return self::entry($version, $semitones, $version->capo, null, null, $options);
    }

    public static function response(DomPdf $pdf, string $filename, bool $inline): Response
    {
        return $inline ? $pdf->stream($filename) : $pdf->download($filename);
    }

    /**
     * @param  list<array<string, mixed>>  $entries
     * @param  array<string, mixed>  $options
     */
    private static function build(string $title, ?Playlist $playlist, array $entries, array $options): DomPdf
    {
        $pdf = Pdf::setOption([
            'defaultFont' => 'DejaVu Sans',
            'isRemoteEnabled' => false,
            'isPhpEnabled' => false,
            'dpi' => 96,
        ])->loadView('pdf.songbook', [
            'title' => $title,
            'playlist' => $playlist,
            'entries' => $entries,
            'options' => $options,
        ])->setPaper('a4', $options['orientation']);

        $pdf->render();
        self::addPageNumbers($pdf, $title);

        return $pdf;
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    private static function entry(SongVersion $version, int $semitones, int $capo, ?int $number, ?string $itemNotes, array $options): array
    {
        $song = $version->song;
        $sheet = Transposer::forVersion($version, $semitones, $capo);
        $lines = $sheet['lines'];

        if (! $options['lyrics']) {
            $lines = array_values(array_filter(
                $lines,
                fn (array $l) => in_array($l['type'], [ChordSheet::LINE_CHORDS, ChordSheet::LINE_SECTION, ChordSheet::LINE_BLANK, ChordSheet::LINE_TAB], true),
            ));
        }

        [$fontSize, $wrap] = self::fitFontSize($lines, $options);

        return [
            'number' => $number,
            'song' => $song,
            'version_label' => $song->versions->count() > 1 ? $version->name : null,
            'key' => $sheet['key'],
            'sounding_key' => $sheet['sounding_key'],
            'capo' => $sheet['capo'],
            'semitones' => $sheet['semitones'],
            'blocks' => self::blocks($lines),
            'font_size' => $fontSize,
            'wrap' => $wrap,
            'notes' => array_values(array_filter([$itemNotes, $song->notes])),
        ];
    }

    /**
     * Reduz a fonte da música quando a linha mais longa não cabe na largura útil da página,
     * para não cortar acordes nem desalinhar a cifra. Abaixo do mínimo, ativa quebra de linha.
     *
     * @param  list<array{type: string, text: string}>  $lines
     * @param  array<string, mixed>  $options
     * @return array{0: float, 1: bool}
     */
    private static function fitFontSize(array $lines, array $options): array
    {
        $pageWidth = $options['orientation'] === 'landscape' ? 841.89 : 595.28;
        $usable = $pageWidth - 2 * $options['margin'] * self::PT_PER_MM - 4;
        $longest = max([1, ...array_map(fn (array $l) => mb_strlen($l['text']), $lines)]);
        $needed = $longest * self::MONO_ADVANCE * $options['font_size'];

        if ($needed <= $usable) {
            return [$options['font_size'], false];
        }

        $fitted = floor($usable / ($longest * self::MONO_ADVANCE) * 10) / 10;

        return $fitted >= self::MIN_FONT_SIZE ? [$fitted, false] : [self::MIN_FONT_SIZE, true];
    }

    /**
     * Agrupa linha de acordes + linha de letra seguinte num bloco indivisível na quebra de página.
     *
     * @param  list<array{type: string, text: string}>  $lines
     * @return list<list<array{type: string, text: string}>>
     */
    private static function blocks(array $lines): array
    {
        $blocks = [];
        $count = count($lines);

        for ($i = 0; $i < $count; $i++) {
            $block = [$lines[$i]];

            while ($i + 1 < $count
                && end($block)['type'] !== ChordSheet::LINE_LYRICS
                && end($block)['type'] !== ChordSheet::LINE_BLANK
                && in_array($lines[$i + 1]['type'], [ChordSheet::LINE_CHORDS, ChordSheet::LINE_LYRICS], true)
                && count($block) < 4) {
                $block[] = $lines[++$i];
            }

            $blocks[] = $block;
        }

        return $blocks;
    }

    private static function addPageNumbers(DomPdf $pdf, string $title): void
    {
        $dompdf = $pdf->getDomPDF();
        $canvas = $dompdf->getCanvas();
        $font = $dompdf->getFontMetrics()->getFont('DejaVu Sans');
        $label = mb_strimwidth($title, 0, 70, '…');

        $canvas->page_text(28, $canvas->get_height() - 22, $label, $font, 7, [0.55, 0.55, 0.55]);
        $canvas->page_text($canvas->get_width() - 70, $canvas->get_height() - 22, '{PAGE_NUM} / {PAGE_COUNT}', $font, 7, [0.55, 0.55, 0.55]);
    }
}

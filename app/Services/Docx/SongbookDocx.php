<?php

namespace App\Services\Docx;

use App\Models\Playlist;
use App\Models\SongVersion;
use App\Services\Chords\ChordSheet;
use App\Services\Pdf\SongbookPdf;
use Illuminate\Http\Response;
use RuntimeException;
use ZipArchive;

/**
 * Gera o mesmo conteúdo do PDF como documento do Word (.docx) editável, para acrescentar textos da missa.
 *
 * As entradas (ordem, tons, versões, capo, tamanho de fonte por música) vêm de SongbookPdf. A formatação usa
 * estilos nomeados do Word ("Cifra", "Acorde", "Seção", "Informações da música"…), então alterar um estilo
 * muda todas as músicas de uma vez. Títulos usam "Título 1" e aparecem no painel de navegação do Word.
 * O arquivo é montado à mão (OOXML mínimo em ZIP), sem armazenar nada no servidor.
 */
final class SongbookDocx
{
    public const CONTENT_TYPE = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';

    private const TWIPS_PER_MM = 56.6929;

    private const MONO_FONT = 'Courier New';

    private const SANS_FONT = 'Arial';

    private const CHORD_COLOR = 'D97757';

    private const NS = 'xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"';

    /**
     * @param  array{orientation: string, font_size: float, margin: int, cover: bool, toc: bool, page_break: bool, lyrics: bool, notes: bool}  $options
     */
    public static function forPlaylist(Playlist $playlist, array $options): string
    {
        return self::build($playlist->name, $playlist, SongbookPdf::playlistEntries($playlist, $options), $options);
    }

    /**
     * @param  array{orientation: string, font_size: float, margin: int, cover: bool, toc: bool, page_break: bool, lyrics: bool, notes: bool}  $options
     */
    public static function forVersion(SongVersion $version, int $semitones, array $options): string
    {
        $options['cover'] = false;
        $options['toc'] = false;

        return self::build($version->song->title, null, [SongbookPdf::versionEntry($version, $semitones, $options)], $options);
    }

    public static function response(string $docx, string $filename): Response
    {
        return response($docx, 200, [
            'Content-Type' => self::CONTENT_TYPE,
            'Content-Disposition' => 'attachment; filename='.$filename,
            'Content-Length' => (string) strlen($docx),
        ]);
    }

    /**
     * @param  list<array<string, mixed>>  $entries
     * @param  array<string, mixed>  $options
     */
    private static function build(string $title, ?Playlist $playlist, array $entries, array $options): string
    {
        $page = self::page($options);
        $body = self::body($playlist, $entries, $options, $page);

        $parts = [
            '[Content_Types].xml' => self::contentTypes(),
            '_rels/.rels' => self::packageRels(),
            'docProps/core.xml' => self::coreProperties($title),
            'docProps/app.xml' => self::appProperties(),
            'word/_rels/document.xml.rels' => self::documentRels(),
            'word/document.xml' => self::document($body, $page),
            'word/styles.xml' => self::styles(),
            'word/settings.xml' => self::settings(),
            'word/footer1.xml' => self::footer($title, $page),
        ];

        $path = tempnam(sys_get_temp_dir(), 'docx');
        $zip = new ZipArchive;

        if ($path === false || $zip->open($path, ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Não foi possível gerar o arquivo do Word.');
        }

        foreach ($parts as $name => $xml) {
            $zip->addFromString($name, $xml);
        }

        $zip->close();
        $docx = (string) file_get_contents($path);
        @unlink($path);

        return $docx;
    }

    /**
     * Página A4 em twips (1/20 de ponto), com as mesmas margens do PDF.
     *
     * @param  array<string, mixed>  $options
     * @return array{width: int, height: int, landscape: bool, margin: int, bottom: int, usable_width: int, usable_height: int}
     */
    private static function page(array $options): array
    {
        $landscape = $options['orientation'] === 'landscape';
        [$width, $height] = $landscape ? [16838, 11906] : [11906, 16838];
        $margin = (int) round($options['margin'] * self::TWIPS_PER_MM);
        $bottom = (int) round(max($options['margin'], 14) * self::TWIPS_PER_MM);

        return [
            'width' => $width,
            'height' => $height,
            'landscape' => $landscape,
            'margin' => $margin,
            'bottom' => $bottom,
            'usable_width' => $width - 2 * $margin,
            'usable_height' => $height - $margin - $bottom,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $entries
     * @param  array<string, mixed>  $options
     * @param  array<string, int|bool>  $page
     */
    private static function body(?Playlist $playlist, array $entries, array $options, array $page): string
    {
        $xml = '';
        $first = true;

        if ($options['cover'] && $playlist) {
            $count = count($entries).' '.(count($entries) === 1 ? 'música' : 'músicas');
            $meta = $count.($playlist->event_date ? ' · '.$playlist->event_date->format('d/m/Y') : '');

            $xml .= self::paragraph(self::run($playlist->name, ['size' => 52, 'bold' => true]), [
                'jc' => 'center', 'before' => (int) round($page['usable_height'] * 0.38), 'after' => 160,
            ]);
            $xml .= self::paragraph(self::run($meta, ['size' => 24, 'color' => '4B5563']), ['jc' => 'center']);

            if ($playlist->description) {
                $xml .= self::paragraph(self::runs($playlist->description, ['size' => 20, 'color' => '6B7280']), ['jc' => 'center', 'before' => 280]);
            }

            $first = false;
        }

        if ($options['toc'] && $playlist) {
            $xml .= self::paragraph(self::run('Sumário', ['size' => 30, 'bold' => true]), ['after' => 200, 'page_break' => ! $first]);
            $xml .= self::tocTable($entries, $page['usable_width']);
            $first = false;
        }

        foreach ($entries as $entry) {
            $xml .= self::song($entry, $options, $first ? null : ($options['page_break'] ? 'page' : 'gap'));
            $first = false;
        }

        return $xml;
    }

    /**
     * @param  array<string, mixed>  $entry
     * @param  array<string, mixed>  $options
     * @param  'page'|'gap'|null  $separator
     */
    private static function song(array $entry, array $options, ?string $separator): string
    {
        $song = $entry['song'];

        $heading = ($entry['number'] ? self::run($entry['number'].'. ', ['color' => '6B7280']) : '')
            .self::run($song->title)
            .($entry['version_label'] ? self::run(' ('.$entry['version_label'].')', ['color' => '6B7280']) : '');

        $xml = self::paragraph($heading, [
            'style' => 'Heading1',
            'page_break' => $separator === 'page',
            'before' => $separator === 'gap' ? 360 : null,
        ]);

        $info = collect([
            $song->artist,
            $entry['key'] ? 'Tom: '.$entry['key'] : null,
            $entry['capo'] ? 'Capo na '.$entry['capo'].'ª casa'.($entry['sounding_key'] ? ' (soa em '.$entry['sounding_key'].')' : '') : null,
        ])->filter()->join(' · ');

        $xml .= self::paragraph($info === '' ? '' : self::run($info), ['style' => 'SongInfo']);

        if ($options['notes'] && $entry['notes']) {
            $xml .= self::paragraph(self::runs(implode(' · ', $entry['notes'])), ['style' => 'SongNotes']);
        }

        $size = (int) floor($entry['font_size'] * 2);
        $sectionSize = (int) max(12, floor($entry['font_size'] * 2 * 0.85));

        foreach ($entry['blocks'] as $block) {
            $last = count($block) - 1;

            foreach ($block as $index => $line) {
                $keepNext = $index < $last;

                $xml .= match ($line['type']) {
                    ChordSheet::LINE_SECTION => self::paragraph(self::run($line['text'], ['size' => $sectionSize]), ['style' => 'SongSection', 'size' => $sectionSize]),
                    ChordSheet::LINE_CHORDS => self::paragraph(self::run($line['text'], ['style' => 'Chord', 'size' => $size]), ['style' => 'Sheet', 'size' => $size, 'keep_next' => $keepNext]),
                    ChordSheet::LINE_TAB => self::paragraph(self::run($line['text'], ['size' => $size, 'color' => '4B5563']), ['style' => 'Sheet', 'size' => $size, 'keep_next' => $keepNext]),
                    default => self::paragraph($line['text'] === '' ? '' : self::run($line['text'], ['size' => $size]), ['style' => 'Sheet', 'size' => $size, 'keep_next' => $keepNext]),
                };
            }
        }

        return $xml;
    }

    /**
     * @param  list<array<string, mixed>>  $entries
     */
    private static function tocTable(array $entries, int $width): string
    {
        $columns = [500, $width - 500 - 1900, 1900];
        $border = '<w:tcBorders><w:bottom w:val="single" w:sz="4" w:space="0" w:color="E5E7EB"/></w:tcBorders>';
        $rows = '';

        foreach ($entries as $entry) {
            $song = $entry['song'];
            $key = $entry['sounding_key'] ?? $entry['key'];
            $cells = [
                self::paragraph(self::run($entry['number'].'.', ['color' => '6B7280']), ['after' => 60, 'before' => 60]),
                self::paragraph(self::run($song->title).($song->artist ? self::run(' — '.$song->artist, ['color' => '6B7280']) : ''), ['after' => 60, 'before' => 60]),
                self::paragraph(
                    ($key ? self::run($key, ['bold' => true, 'color' => self::CHORD_COLOR]) : '')
                    .($entry['capo'] ? self::run(' (capo '.$entry['capo'].')', ['color' => '6B7280']) : ''),
                    ['jc' => 'right', 'after' => 60, 'before' => 60],
                ),
            ];

            $rows .= '<w:tr><w:trPr><w:cantSplit/></w:trPr>';

            foreach ($cells as $i => $cell) {
                $rows .= '<w:tc><w:tcPr><w:tcW w:w="'.$columns[$i].'" w:type="dxa"/>'.$border.'</w:tcPr>'.$cell.'</w:tc>';
            }

            $rows .= '</w:tr>';
        }

        return '<w:tbl><w:tblPr><w:tblW w:w="'.$width.'" w:type="dxa"/><w:tblLayout w:type="fixed"/>'
            .'<w:tblCellMar><w:left w:w="0" w:type="dxa"/><w:right w:w="80" w:type="dxa"/></w:tblCellMar></w:tblPr>'
            .'<w:tblGrid>'.implode('', array_map(fn (int $w) => '<w:gridCol w:w="'.$w.'"/>', $columns)).'</w:tblGrid>'
            .$rows.'</w:tbl>'
            .self::paragraph('');
    }

    /**
     * @param  array{style?: string, jc?: string, before?: ?int, after?: int, page_break?: bool, keep_next?: bool, size?: int}  $props
     */
    private static function paragraph(string $runs, array $props = []): string
    {
        $pPr = '';

        if (isset($props['style'])) {
            $pPr .= '<w:pStyle w:val="'.$props['style'].'"/>';
        }

        if ($props['keep_next'] ?? false) {
            $pPr .= '<w:keepNext/>';
        }

        if ($props['page_break'] ?? false) {
            $pPr .= '<w:pageBreakBefore/>';
        }

        if (isset($props['before']) || isset($props['after'])) {
            $pPr .= '<w:spacing'
                .(isset($props['before']) ? ' w:before="'.$props['before'].'"' : '')
                .(isset($props['after']) ? ' w:after="'.$props['after'].'"' : '')
                .'/>';
        }

        if (isset($props['jc'])) {
            $pPr .= '<w:jc w:val="'.$props['jc'].'"/>';
        }

        // Tamanho da marca de parágrafo: mantém linhas em branco com a mesma altura da cifra.
        if (isset($props['size'])) {
            $pPr .= '<w:rPr><w:sz w:val="'.$props['size'].'"/><w:szCs w:val="'.$props['size'].'"/></w:rPr>';
        }

        return '<w:p>'.($pPr !== '' ? '<w:pPr>'.$pPr.'</w:pPr>' : '').$runs.'</w:p>';
    }

    /**
     * @param  array{style?: string, size?: int, bold?: bool, color?: string}  $props
     */
    private static function run(string $text, array $props = []): string
    {
        $rPr = (isset($props['style']) ? '<w:rStyle w:val="'.$props['style'].'"/>' : '')
            .(($props['bold'] ?? false) ? '<w:b/><w:bCs/>' : '')
            .(isset($props['color']) ? '<w:color w:val="'.$props['color'].'"/>' : '')
            .(isset($props['size']) ? '<w:sz w:val="'.$props['size'].'"/><w:szCs w:val="'.$props['size'].'"/>' : '');

        return '<w:r>'.($rPr !== '' ? '<w:rPr>'.$rPr.'</w:rPr>' : '').'<w:t xml:space="preserve">'.self::escape($text).'</w:t></w:r>';
    }

    /**
     * Texto com quebras de linha (observações, descrição) em runs separados por <w:br/>.
     *
     * @param  array{style?: string, size?: int, bold?: bool, color?: string}  $props
     */
    private static function runs(string $text, array $props = []): string
    {
        $lines = preg_split('/\R/u', $text) ?: [''];

        return implode('<w:r><w:br/></w:r>', array_map(fn (string $line) => self::run($line, $props), $lines));
    }

    /**
     * Escapa para XML e remove caracteres de controle que tornariam o arquivo inválido no Word.
     */
    private static function escape(string $text): string
    {
        $text = str_replace("\t", '    ', $text);
        $text = (string) preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text);

        return htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    /**
     * @param  array<string, int|bool>  $page
     */
    private static function document(string $body, array $page): string
    {
        $orient = $page['landscape'] ? ' w:orient="landscape"' : '';

        return self::xml('<w:document '.self::NS.'><w:body>'.$body
            .'<w:sectPr><w:footerReference w:type="default" r:id="rIdFooter"/>'
            .'<w:pgSz w:w="'.$page['width'].'" w:h="'.$page['height'].'"'.$orient.'/>'
            .'<w:pgMar w:top="'.$page['margin'].'" w:right="'.$page['margin'].'" w:bottom="'.$page['bottom'].'" w:left="'.$page['margin'].'" w:header="425" w:footer="440" w:gutter="0"/>'
            .'</w:sectPr></w:body></w:document>');
    }

    /**
     * Rodapé igual ao do PDF: título à esquerda e "página / total" à direita, em cinza.
     *
     * @param  array<string, int|bool>  $page
     */
    private static function footer(string $title, array $page): string
    {
        $rPr = '<w:rPr><w:color w:val="8C8C8C"/><w:sz w:val="14"/><w:szCs w:val="14"/></w:rPr>';
        $field = fn (string $instr) => '<w:fldSimple w:instr=" '.$instr.' "><w:r>'.$rPr.'<w:t>1</w:t></w:r></w:fldSimple>';

        return self::xml('<w:ftr '.self::NS.'><w:p><w:pPr><w:pStyle w:val="Footer"/><w:tabs><w:tab w:val="right" w:pos="'.$page['usable_width'].'"/></w:tabs></w:pPr>'
            .'<w:r>'.$rPr.'<w:t xml:space="preserve">'.self::escape(mb_strimwidth($title, 0, 70, '…')).'</w:t></w:r>'
            .'<w:r>'.$rPr.'<w:tab/></w:r>'
            .$field('PAGE')
            .'<w:r>'.$rPr.'<w:t xml:space="preserve"> / </w:t></w:r>'
            .$field('NUMPAGES')
            .'</w:p></w:ftr>');
    }

    private static function styles(): string
    {
        $sans = '<w:rFonts w:ascii="'.self::SANS_FONT.'" w:hAnsi="'.self::SANS_FONT.'" w:cs="'.self::SANS_FONT.'" w:eastAsia="'.self::SANS_FONT.'"/>';
        $mono = '<w:rFonts w:ascii="'.self::MONO_FONT.'" w:hAnsi="'.self::MONO_FONT.'" w:cs="'.self::MONO_FONT.'" w:eastAsia="'.self::MONO_FONT.'"/>';

        return self::xml('<w:styles '.self::NS.'>'
            .'<w:docDefaults><w:rPrDefault><w:rPr>'.$sans.'<w:color w:val="111827"/><w:sz w:val="20"/><w:szCs w:val="20"/><w:lang w:val="pt-BR" w:eastAsia="pt-BR" w:bidi="ar-SA"/></w:rPr></w:rPrDefault>'
            .'<w:pPrDefault><w:pPr><w:spacing w:after="0" w:line="240" w:lineRule="auto"/></w:pPr></w:pPrDefault></w:docDefaults>'
            .'<w:style w:type="paragraph" w:default="1" w:styleId="Normal"><w:name w:val="Normal"/><w:qFormat/></w:style>'
            .'<w:style w:type="paragraph" w:styleId="Heading1"><w:name w:val="heading 1"/><w:basedOn w:val="Normal"/><w:next w:val="SongInfo"/><w:qFormat/>'
            .'<w:pPr><w:keepNext/><w:keepLines/><w:spacing w:after="40"/><w:outlineLvl w:val="0"/></w:pPr><w:rPr><w:b/><w:bCs/><w:sz w:val="28"/><w:szCs w:val="28"/></w:rPr></w:style>'
            .'<w:style w:type="paragraph" w:customStyle="1" w:styleId="SongInfo"><w:name w:val="Informações da música"/><w:basedOn w:val="Normal"/><w:next w:val="Sheet"/><w:qFormat/>'
            .'<w:pPr><w:keepNext/><w:pBdr><w:bottom w:val="single" w:sz="8" w:space="4" w:color="D1D5DB"/></w:pBdr><w:spacing w:after="160"/></w:pPr><w:rPr><w:color w:val="4B5563"/><w:sz w:val="19"/><w:szCs w:val="19"/></w:rPr></w:style>'
            .'<w:style w:type="paragraph" w:customStyle="1" w:styleId="SongNotes"><w:name w:val="Observação"/><w:basedOn w:val="Normal"/><w:next w:val="Sheet"/><w:qFormat/>'
            .'<w:pPr><w:keepNext/><w:shd w:val="clear" w:color="auto" w:fill="FFFBEB"/><w:spacing w:after="160"/></w:pPr><w:rPr><w:color w:val="92400E"/><w:sz w:val="18"/><w:szCs w:val="18"/></w:rPr></w:style>'
            .'<w:style w:type="paragraph" w:customStyle="1" w:styleId="Sheet"><w:name w:val="Cifra"/><w:basedOn w:val="Normal"/><w:qFormat/>'
            .'<w:pPr><w:keepLines/><w:spacing w:after="0" w:line="300" w:lineRule="auto"/></w:pPr><w:rPr>'.$mono.'<w:noProof/><w:sz w:val="22"/><w:szCs w:val="22"/></w:rPr></w:style>'
            .'<w:style w:type="paragraph" w:customStyle="1" w:styleId="SongSection"><w:name w:val="Seção"/><w:basedOn w:val="Normal"/><w:next w:val="Sheet"/><w:qFormat/>'
            .'<w:pPr><w:keepNext/><w:spacing w:before="80" w:after="0"/></w:pPr><w:rPr><w:b/><w:bCs/><w:caps/><w:color w:val="6B7280"/></w:rPr></w:style>'
            .'<w:style w:type="character" w:customStyle="1" w:styleId="Chord"><w:name w:val="Acorde"/><w:qFormat/><w:rPr><w:b/><w:bCs/><w:noProof/><w:color w:val="'.self::CHORD_COLOR.'"/></w:rPr></w:style>'
            .'<w:style w:type="paragraph" w:styleId="Footer"><w:name w:val="footer"/><w:basedOn w:val="Normal"/><w:rPr><w:color w:val="8C8C8C"/><w:sz w:val="14"/><w:szCs w:val="14"/></w:rPr></w:style>'
            .'</w:styles>');
    }

    private static function settings(): string
    {
        return self::xml('<w:settings '.self::NS.'><w:defaultTabStop w:val="708"/><w:characterSpacingControl w:val="doNotCompress"/>'
            .'<w:compat><w:compatSetting w:name="compatibilityMode" w:uri="http://schemas.microsoft.com/office/word" w:val="15"/></w:compat></w:settings>');
    }

    private static function contentTypes(): string
    {
        $base = 'application/vnd.openxmlformats-officedocument.wordprocessingml.';

        return self::xml('<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/word/document.xml" ContentType="'.$base.'document.main+xml"/>'
            .'<Override PartName="/word/styles.xml" ContentType="'.$base.'styles+xml"/>'
            .'<Override PartName="/word/settings.xml" ContentType="'.$base.'settings+xml"/>'
            .'<Override PartName="/word/footer1.xml" ContentType="'.$base.'footer+xml"/>'
            .'<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>'
            .'<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>'
            .'</Types>');
    }

    private static function packageRels(): string
    {
        return self::xml('<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>'
            .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>'
            .'<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>'
            .'</Relationships>');
    }

    private static function documentRels(): string
    {
        $type = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/';

        return self::xml('<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rIdStyles" Type="'.$type.'styles" Target="styles.xml"/>'
            .'<Relationship Id="rIdSettings" Type="'.$type.'settings" Target="settings.xml"/>'
            .'<Relationship Id="rIdFooter" Type="'.$type.'footer" Target="footer1.xml"/>'
            .'</Relationships>');
    }

    private static function coreProperties(string $title): string
    {
        $now = now()->utc()->format('Y-m-d\TH:i:s\Z');

        return self::xml('<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
            .'<dc:title>'.self::escape($title).'</dc:title><dc:creator>'.self::escape((string) config('app.name')).'</dc:creator><dc:language>pt-BR</dc:language>'
            .'<dcterms:created xsi:type="dcterms:W3CDTF">'.$now.'</dcterms:created><dcterms:modified xsi:type="dcterms:W3CDTF">'.$now.'</dcterms:modified>'
            .'</cp:coreProperties>');
    }

    private static function appProperties(): string
    {
        return self::xml('<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties"><Application>'.self::escape((string) config('app.name')).'</Application></Properties>');
    }

    private static function xml(string $content): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'."\n".$content;
    }
}

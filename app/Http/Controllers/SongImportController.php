<?php

namespace App\Http\Controllers;

use App\Services\Import\CifraClubImporter;
use App\Services\Import\SongFileParser;
use App\Services\Import\SongPdfReader;
use App\Support\YouTube;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

/**
 * Importações nunca salvam direto: pré-preenchem o formulário para revisão antes de salvar.
 */
class SongImportController extends Controller
{
    public function create(CifraClubImporter $importer): View
    {
        return view('songs.import', [
            'externalEnabled' => $importer->isEnabled(),
            'bookmarklet' => self::bookmarklet(route('songs.import')),
        ]);
    }

    /**
     * Favorito do navegador: roda na página do Cifra Club que o usuário abriu, lê a cifra exibida
     * (o maior <pre>), título, artista e vídeo, e abre esta tela com os dados no fragmento (#import=),
     * que nunca é enviado ao servidor. Nenhuma requisição automatizada ao site de origem.
     */
    public static function bookmarklet(string $importUrl): string
    {
        $js = <<<'JS'
            (()=>{const p=[...document.querySelectorAll('pre')].sort((a,b)=>b.innerText.length-a.innerText.length)[0];
            if(!p||p.innerText.trim().length<10){alert('Nenhuma cifra encontrada nesta página.');return}
            const q=s=>(document.querySelector(s)||{}).innerText||'';
            const m=document.documentElement.innerHTML.match(/(?:youtube\.com\/(?:embed\/|watch\?v=)|i\.ytimg\.com\/vi\/|youtu\.be\/)([\w-]{11})/);
            const d={title:(q('h1')||document.title).trim(),artist:q('h2 a,h2').trim(),content:p.innerText,
            url:location.href.split('#')[0],youtube:m?'https://www.youtube.com/watch?v='+m[1]:''};
            const b=btoa(unescape(encodeURIComponent(JSON.stringify(d)))).replace(/\+/g,'-').replace(/\//g,'_').replace(/=+$/,'');
            window.open(__URL__+'#import='+b,'_blank')})()
            JS;

        return 'javascript:'.str_replace('__URL__', json_encode($importUrl, JSON_UNESCAPED_SLASHES), preg_replace('/\n\s*/', '', $js));
    }

    public function paste(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'content' => ['required', 'string', 'max:100000'],
            'title' => ['nullable', 'string', 'max:255'],
            'artist' => ['nullable', 'string', 'max:255'],
            'source' => ['nullable', 'in:colagem,cifraclub'],
            'source_url' => ['nullable', 'url:http,https', 'max:500'],
            'youtube_url' => ['nullable', 'string', 'max:500'],
        ]);

        $parsed = SongFileParser::parse($data['content']);
        $hasTitleDirective = $parsed['title'] !== 'Sem título';

        if (! $hasTitleDirective && blank($data['title'] ?? null)) {
            $firstLine = trim(strtok($data['content'], "\n"));
            $parsed['title'] = mb_substr($firstLine !== '' ? $firstLine : 'Sem título', 0, 255);
        }

        return $this->prefill([
            ...$parsed,
            'title' => filled($data['title'] ?? null) ? $data['title'] : $parsed['title'],
            'artist' => $data['artist'] ?? $parsed['artist'],
            'source' => $data['source'] ?? 'colagem',
            'source_url' => $data['source_url'] ?? null,
            'youtube_url' => YouTube::isValidUrl($data['youtube_url'] ?? null) ? $data['youtube_url'] : null,
        ]);
    }

    public function file(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:5120', 'extensions:pdf,txt,cho,chopro,chordpro,crd,pro'],
        ], ['file.extensions' => 'Envie um PDF ou arquivo .txt, .cho, .chopro, .chordpro, .crd ou .pro.']);

        $file = $request->file('file');
        $isPdf = strtolower($file->getClientOriginalExtension()) === 'pdf';

        if (! $isPdf && $file->getSize() > 512 * 1024) {
            return back()->withErrors(['file' => 'Arquivos de texto podem ter no máximo 512 KB.']);
        }

        if ($isPdf) {
            if ($file->getMimeType() !== 'application/pdf') {
                return back()->withErrors(['file' => 'O arquivo não é um PDF válido.']);
            }

            try {
                return $this->prefill(SongPdfReader::parse($file->get(), $file->getClientOriginalName()));
            } catch (RuntimeException $e) {
                return back()->withErrors(['file' => $e->getMessage()]);
            }
        }

        return $this->prefill(SongFileParser::parse($file->get(), $file->getClientOriginalName()));
    }

    public function external(Request $request, CifraClubImporter $importer): RedirectResponse
    {
        $request->validate(['reference' => ['required', 'string', 'max:300']]);

        $reference = CifraClubImporter::parseReference($request->input('reference'));

        if ($reference === null) {
            return back()->withInput()->withErrors(['reference' => 'Use o link da música no Cifra Club ou "artista/musica".']);
        }

        try {
            return $this->prefill($importer->fetch(...$reference));
        } catch (RuntimeException $e) {
            return back()->withInput()->withErrors(['reference' => $e->getMessage()]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function prefill(array $data): RedirectResponse
    {
        return redirect()->route('songs.create')
            ->withInput(array_filter($data, fn ($v) => $v !== null))
            ->with('success', 'Confira os dados importados e clique em Salvar.');
    }
}

<x-layouts.app title="Importar cifra">
    <x-page-header title="Importar cifra" subtitle="Toda importação abre o formulário para você revisar antes de salvar." />

    <section class="card mb-6 space-y-3 border-indigo-200 bg-indigo-50/40 p-5">
        <h2 class="font-semibold">Salvar do Cifra Club com um clique</h2>
        <ol class="list-decimal space-y-1 pl-5 text-sm text-slate-700">
            <li>Arraste este botão para a barra de favoritos do navegador (uma vez só):
                <a href="{{ $bookmarklet }}" class="btn-primary ml-1 cursor-grab py-1" data-testid="bookmarklet" onclick="event.preventDefault(); toast('Arraste para a barra de favoritos', 'info')">♪ Salvar no Cifra Ivo</a>
            </li>
            <li>Abra a música no Cifra Club (no tom que quiser) e clique no favorito.</li>
            <li>Esta tela abre com título, artista, cifra, link e vídeo preenchidos. Revise e salve.</li>
        </ol>
        <p class="text-xs text-slate-500">
            O favorito apenas lê a cifra que já está na sua tela, como um copiar/colar. O Cifra Club bloqueia acessos automatizados,
            então o sistema não busca músicas sozinho. No celular, use “Colar cifra” abaixo. Respeite os direitos dos autores: uso pessoal.
        </p>
    </section>

    <div class="grid gap-6 lg:grid-cols-2">
        <form method="POST" action="{{ route('songs.import.paste') }}" class="card space-y-4 p-5" id="paste-form">
            @csrf
            <input type="hidden" name="source" value="{{ old('source', 'colagem') }}">
            <input type="hidden" name="source_url" value="{{ old('source_url') }}">
            <input type="hidden" name="youtube_url" value="{{ old('youtube_url') }}">
            <h2 class="font-semibold">Colar cifra</h2>
            <div class="grid gap-3 sm:grid-cols-2">
                <div><label for="p-title" class="label">Título</label><input id="p-title" name="title" value="{{ old('title') }}" class="input" placeholder="Se vazio, usa a 1ª linha"></div>
                <div><label for="p-artist" class="label">Artista</label><input id="p-artist" name="artist" value="{{ old('artist') }}" class="input"></div>
            </div>
            <div>
                <label for="p-content" class="label">Texto da cifra (texto puro ou ChordPro)</label>
                <textarea id="p-content" name="content" rows="14" wrap="off" spellcheck="false" class="input font-mono text-[13px] whitespace-pre" required>{{ old('content') }}</textarea>
                @error('content')<p class="error">{{ $message }}</p>@enderror
            </div>
            <button class="btn-primary">Continuar</button>
        </form>

        <div class="space-y-6">
            <form method="POST" action="{{ route('songs.import.file') }}" enctype="multipart/form-data" class="card space-y-4 p-5">
                @csrf
                <h2 class="font-semibold">Arquivo PDF, texto ou ChordPro</h2>
                <div>
                    <label for="file" class="label">PDF (até 5 MB) ou .txt, .cho, .chopro, .chordpro, .crd, .pro (até 512 KB)</label>
                    <input id="file" name="file" type="file" accept=".pdf,application/pdf,.txt,.cho,.chopro,.chordpro,.crd,.pro,text/plain" required class="block w-full text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:font-medium file:text-indigo-700">
                    @error('file')<p class="error">{{ $message }}</p>@enderror
                </div>
                <p class="text-xs text-slate-500">PDF: o texto é lido e o alinhamento dos acordes é reconstruído (confira antes de salvar; PDF escaneado/foto não funciona). ChordPro: <code>{title}</code>, <code>{artist}</code> e <code>{key}</code> preenchem os campos.</p>
                <button class="btn-primary">Ler arquivo</button>
            </form>

            <form method="POST" action="{{ route('songs.import.external') }}" class="card space-y-4 p-5">
                @csrf
                <h2 class="font-semibold">Importação externa (opcional)</h2>
                @if ($externalEnabled)
                    <div>
                        <label for="reference" class="label">Link do Cifra Club ou “artista/musica”</label>
                        <input id="reference" name="reference" value="{{ old('reference') }}" class="input" placeholder="https://www.cifraclub.com.br/artista/musica/">
                        @error('reference')<p class="error">{{ $message }}</p>@enderror
                    </div>
                    <p class="text-xs text-slate-500">Uma música por vez, para uso pessoal. Respeite os direitos dos autores e os termos do site de origem.</p>
                    <button class="btn-secondary">Buscar</button>
                @else
                    <p class="text-sm text-slate-600">Desativada. Requer uma instância própria do <code>code4music/cifraclub-api</code> (<code>CIFRACLUB_API_URL</code> no <code>.env</code>). Em out/2026 o Cifra Club responde 403 a acessos automatizados, então prefira o favorito acima.</p>
                @endif
            </form>
        </div>
    </div>
    <script>
        // Dados enviados pelo favorito no fragmento da URL (não vão ao servidor até você confirmar o envio).
        (() => {
            const match = location.hash.match(/^#import=([A-Za-z0-9_-]+)$/);
            if (!match) {
                return;
            }
            history.replaceState(null, '', location.pathname + location.search);
            try {
                const b64 = match[1].replace(/-/g, '+').replace(/_/g, '/');
                const data = JSON.parse(decodeURIComponent(escape(atob(b64))));
                const form = document.getElementById('paste-form');
                form.elements.title.value = String(data.title || '').slice(0, 255);
                form.elements.artist.value = String(data.artist || '').slice(0, 255);
                form.elements.content.value = String(data.content || '');
                form.elements.source.value = 'cifraclub';
                form.elements.source_url.value = /^https?:\/\//.test(data.url || '') ? data.url : '';
                form.elements.youtube_url.value = String(data.youtube || '');
                form.requestSubmit();
            } catch {
                document.addEventListener('DOMContentLoaded', () => toast('Não foi possível ler os dados do favorito', 'error'));
            }
        })();
    </script>
</x-layouts.app>

@php($editing = $playlist->exists)
<x-layouts.app :title="$editing ? 'Editar playlist' : 'Nova playlist'">
    <x-page-header :title="$editing ? 'Editar playlist' : 'Nova playlist'" />
    <form method="POST" action="{{ $editing ? route('playlists.update', $playlist) : route('playlists.store') }}" class="card max-w-2xl space-y-4 p-5" enctype="multipart/form-data">
        @csrf
        @if ($editing) @method('PUT') @endif
        <div>
            <label for="name" class="label">Nome *</label>
            <input id="name" name="name" value="{{ old('name', $playlist->name) }}" required maxlength="255" placeholder="Ex.: Missa de Domingo" class="input">
            @error('name')<p class="error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="event_date" class="label">Data do evento</label>
            <input id="event_date" name="event_date" type="date" value="{{ old('event_date', $playlist->event_date?->format('Y-m-d')) }}" class="input sm:w-56">
            @error('event_date')<p class="error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="description" class="label">Descrição</label>
            <textarea id="description" name="description" rows="3" class="input">{{ old('description', $playlist->description) }}</textarea>
            @error('description')<p class="error">{{ $message }}</p>@enderror
        </div>

        <fieldset class="space-y-4 border-t border-slate-100 pt-4">
            <legend class="font-semibold">Capa da missa <span class="text-sm font-normal text-slate-500">(opcional)</span></legend>
            <p class="-mt-2 text-xs text-slate-500">Aparece na primeira página do PDF e do Word e no topo do link online. Deixe em branco para não usar.</p>
            <div>
                <label for="cover_title" class="label">Título da capa</label>
                <input id="cover_title" name="cover_title" value="{{ old('cover_title', $playlist->cover_title) }}" maxlength="255" placeholder="Ex.: Todos os Santos – Solenidade 2026 (vazio = nome da playlist)" class="input">
                @error('cover_title')<p class="error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="cover_image" class="label">Imagem</label>
                @if ($playlist->cover_image)
                    <div class="mb-2 flex items-center gap-3">
                        <img src="{{ route('playlists.cover', $playlist) }}" alt="Imagem atual da capa" class="h-20 w-auto rounded border border-slate-200">
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="remove_cover_image" value="1" class="rounded border-slate-300 text-red-600"> Remover imagem
                        </label>
                    </div>
                @endif
                <input id="cover_image" name="cover_image" type="file" accept="image/jpeg,image/png" class="block w-full text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:font-medium file:text-indigo-700">
                <p class="mt-1 text-xs text-slate-500">JPG ou PNG, até 5 MB.{{ $playlist->cover_image ? ' Enviar outra substitui a atual.' : '' }}</p>
                @error('cover_image')<p class="error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="cover_quote" class="label">Passagem / frase</label>
                <textarea id="cover_quote" name="cover_quote" rows="3" maxlength="1000" class="input" placeholder="Alegrai-vos e exultai, porque será grande a vossa recompensa nos céus.">{{ old('cover_quote', $playlist->cover_quote) }}</textarea>
                @error('cover_quote')<p class="error">{{ $message }}</p>@enderror
            </div>
        </fieldset>
        <div class="flex gap-2">
            <button class="btn-primary">{{ $editing ? 'Salvar' : 'Criar playlist' }}</button>
            <a href="{{ $editing ? route('playlists.show', $playlist) : route('playlists.index') }}" class="btn-secondary">Cancelar</a>
        </div>
    </form>
</x-layouts.app>

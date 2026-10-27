@php($editing = $playlist->exists)
<x-layouts.app :title="$editing ? 'Editar playlist' : 'Nova playlist'">
    <x-page-header :title="$editing ? 'Editar playlist' : 'Nova playlist'" />
    <form method="POST" action="{{ $editing ? route('playlists.update', $playlist) : route('playlists.store') }}" class="card max-w-2xl space-y-4 p-5">
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
        <div class="flex gap-2">
            <button class="btn-primary">{{ $editing ? 'Salvar' : 'Criar playlist' }}</button>
            <a href="{{ $editing ? route('playlists.show', $playlist) : route('playlists.index') }}" class="btn-secondary">Cancelar</a>
        </div>
    </form>
</x-layouts.app>

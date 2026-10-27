<x-layouts.app title="Tags">
    <x-page-header title="Tags" subtitle="Organize as músicas por momento da celebração, estilo ou o que preferir." />

    <div class="grid gap-6 lg:grid-cols-[1fr_20rem]">
        <div class="card divide-y divide-slate-100">
            @forelse ($tags as $tag)
                <div class="flex items-center gap-3 px-4 py-3" data-testid="tag-row">
                    <span class="size-3 shrink-0 rounded-full" style="background-color: {{ $tag->color ?: '#cbd5e1' }}"></span>
                    <a href="{{ route('songs.index', ['tags' => [$tag->id]]) }}" class="min-w-0 flex-1 truncate font-medium hover:text-indigo-600">{{ $tag->name }}</a>
                    <span class="text-sm text-slate-500 tabular-nums">{{ $tag->songs_count }}</span>
                    <a href="{{ route('tags.index', ['edit' => $tag->id]) }}" class="rounded-md p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700" aria-label="Editar {{ $tag->name }}"><x-icon name="pencil" class="size-4" /></a>
                    <form method="POST" action="{{ route('tags.destroy', $tag) }}" data-confirm="Excluir a tag “{{ $tag->name }}”?" data-confirm-text="As músicas não serão excluídas." data-confirm-button="Excluir">
                        @csrf @method('DELETE')
                        <button class="rounded-md p-1.5 text-slate-400 hover:bg-red-50 hover:text-red-600" aria-label="Excluir {{ $tag->name }}"><x-icon name="trash" class="size-4" /></button>
                    </form>
                </div>
            @empty
                <div class="px-4 py-10 text-center text-sm text-slate-500">Nenhuma tag ainda.</div>
            @endforelse
        </div>

        <form method="POST" action="{{ $editing ? route('tags.update', $editing) : route('tags.store') }}" class="card h-fit space-y-4 p-5">
            @csrf
            @if ($editing) @method('PUT') @endif
            <h2 class="font-semibold">{{ $editing ? 'Editar tag' : 'Nova tag' }}</h2>
            <div>
                <label for="name" class="label">Nome</label>
                <input id="name" name="name" value="{{ old('name', $editing?->name) }}" required maxlength="80" class="input" @if ($editing) autofocus @endif>
                @error('name')<p class="error">{{ $message }}</p>@enderror
                @error('slug')<p class="error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="color" class="label">Cor</label>
                <input id="color" name="color" type="color" value="{{ old('color', $editing?->color ?? '#6366f1') }}" class="h-10 w-16 cursor-pointer rounded-lg border border-slate-300">
                @error('color')<p class="error">{{ $message }}</p>@enderror
            </div>
            <div class="flex gap-2">
                <button class="btn-primary">{{ $editing ? 'Salvar' : 'Criar tag' }}</button>
                @if ($editing)<a href="{{ route('tags.index') }}" class="btn-secondary">Cancelar</a>@endif
            </div>
        </form>
    </div>
</x-layouts.app>

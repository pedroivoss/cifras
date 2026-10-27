@props(['title' => null])
@php
    $nav = [
        ['dashboard', 'Dashboard', 'home', 'dashboard'],
        ['songs.index', 'Músicas', 'music', 'songs.*'],
        ['playlists.index', 'Playlists', 'list', 'playlists.*'],
        ['tags.index', 'Tags', 'tag', 'tags.*'],
        ['settings', 'Configurações', 'cog', 'settings*'],
    ];
@endphp
<x-layouts.base :title="$title">
    <div x-data="{ menu: false }" class="min-h-full">
        {{-- Topbar mobile --}}
        <header class="sticky top-0 z-30 flex items-center gap-3 border-b border-slate-200 bg-white/95 px-4 py-3 backdrop-blur lg:hidden">
            <button type="button" class="btn-ghost -ml-2 p-2" @click="menu = true" aria-label="Abrir menu">
                <x-icon name="menu" />
            </button>
            <a href="{{ route('dashboard') }}" class="font-semibold tracking-tight">Cifra <span class="text-indigo-600">Ivo Company</span></a>
        </header>

        {{-- Overlay mobile --}}
        <div x-cloak x-show="menu" x-transition.opacity class="fixed inset-0 z-40 bg-slate-900/40 lg:hidden" @click="menu = false"></div>

        <aside :class="menu ? 'translate-x-0' : '-translate-x-full'"
               class="fixed inset-y-0 left-0 z-50 flex w-64 flex-col border-r border-slate-200 bg-white transition-transform lg:translate-x-0">
            <div class="flex items-center justify-between px-5 py-5">
                <a href="{{ route('dashboard') }}" class="text-lg font-semibold tracking-tight">Cifra <span class="text-indigo-600">Ivo Company</span></a>
                <button type="button" class="btn-ghost p-1.5 lg:hidden" @click="menu = false" aria-label="Fechar menu"><x-icon name="x" /></button>
            </div>
            <nav class="flex-1 space-y-1 px-3" aria-label="Principal">
                @foreach ($nav as [$route, $label, $icon, $pattern])
                    <a href="{{ route($route) }}"
                       @class([
                           'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium',
                           'bg-indigo-50 text-indigo-700' => request()->routeIs($pattern),
                           'text-slate-600 hover:bg-slate-100 hover:text-slate-900' => ! request()->routeIs($pattern),
                       ])
                       @if (request()->routeIs($pattern)) aria-current="page" @endif>
                        <x-icon :name="$icon" /> {{ $label }}
                    </a>
                @endforeach
            </nav>
            <div class="border-t border-slate-200 p-3">
                <p class="truncate px-3 pb-2 text-xs text-slate-500">{{ auth()->user()->name }}</p>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">
                        <x-icon name="logout" /> Sair
                    </button>
                </form>
            </div>
        </aside>

        <main class="lg:pl-64">
            <div class="mx-auto max-w-6xl px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
                {{ $slot }}
            </div>
        </main>
    </div>
</x-layouts.base>

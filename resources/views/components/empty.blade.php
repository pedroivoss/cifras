@props(['title', 'icon' => 'music'])
<div class="card flex flex-col items-center px-6 py-12 text-center">
    <div class="mb-3 rounded-full bg-indigo-50 p-3 text-indigo-600"><x-icon :name="$icon" class="size-6" /></div>
    <p class="font-medium text-slate-900">{{ $title }}</p>
    <div class="mt-1 text-sm text-slate-500">{{ $slot }}</div>
</div>

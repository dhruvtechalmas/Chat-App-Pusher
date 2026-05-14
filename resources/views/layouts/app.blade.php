<head>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<x-layouts::app.sidebar :title="$title ?? null">
    <flux:main>
        {{ $slot }}
    </flux:main>
</x-layouts::app.sidebar>

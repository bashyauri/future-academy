<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'Future Academy') }}</title>
    @php($viteManifest = public_path('build/manifest.json'))
    @if (file_exists($viteManifest))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        {{-- Fallback: use CDN Tailwind + Alpine when Vite build is not present to avoid manifest error --}}
        <script src="https://cdn.tailwindcss.com"></script>
        <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @endif
    @livewireStyles
</head>
<body class="bg-gray-50 text-gray-900">
    {{ $slot }}
    @livewireScripts
</body>
</html>

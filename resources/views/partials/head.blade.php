<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta name="csrf-token" content="{{ csrf_token() }}" />


<title>{{ $title ?? config('app.name') }}</title>

<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/icons/logo-32.png') }}">
<link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/icons/logo-16.png') }}">
<link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/icons/logo-180.png') }}">

<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.11/dist/katex.min.css">
<script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.11/dist/katex.min.js"></script>
<script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.11/dist/contrib/auto-render.min.js"
	onload="window.renderMathInElement = window.renderMathInElement || renderMathInElement"></script>

@vite(['resources/css/app.css', 'resources/js/app.js'])
@fluxAppearance

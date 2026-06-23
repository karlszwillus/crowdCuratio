<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Crowd Curatio') }}</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.0.0-alpha/css/bootstrap.css"
          rel="stylesheet">


    <!-- Fonts -->
    <!-- Webfonts kommen über Vite-Bundle aus @fontsource — kein
         Google-Fonts-Roundtrip mehr. Die alte Bootstrap-3-/jQuery-
         Pipeline wird in einer der nächsten Sub-Wellen abgelöst;
         hier bleibt sie als Bestand, weil der Editor sie noch
         braucht. -->
    <script src="https://code.jquery.com/jquery-3.5.1.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

</head>
<body>
<div class="font-sans text-gray-900 antialiased">
    {{ $slot }}
</div>
</body>
</html>

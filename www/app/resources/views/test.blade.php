<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Test</title>
    </head>
    <body>
        <h1>Links</h1>
        <ul>
            @foreach ([url('/test/form'), url('/test/route'), '/test/test'] as $link)
                <li><a href="{{ $link }}">{{ $link }}</a></li>
            @endforeach
        </ul>
    </body>
</html>

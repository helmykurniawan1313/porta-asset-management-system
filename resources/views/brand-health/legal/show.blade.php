<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $page->title }} — {{ config('app.name') }}</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; max-width: 760px; margin: 0 auto; padding: 2.5rem 1.5rem 5rem; line-height: 1.6; color: #1b1b18; }
        h1 { font-size: 1.75rem; margin-bottom: 0.25rem; }
        h2 { font-size: 1.15rem; margin-top: 2.25rem; margin-bottom: 0.5rem; }
        p, li { color: #33302c; }
        .meta { color: #706f6c; font-size: 0.9rem; margin-bottom: 2rem; }
        ul, ol { padding-left: 1.25rem; }
        a { color: #0f6e63; }
        code { background: #f0f0ee; padding: 0.1em 0.35em; border-radius: 0.25em; font-size: 0.9em; }
        @media (prefers-color-scheme: dark) {
            body { background: #0a0a0a; color: #ededec; }
            p, li { color: #c9c8c5; }
            .meta { color: #a1a09a; }
            a { color: #35c9b6; }
            code { background: #1f1f1d; }
        }
    </style>
</head>
<body>
    <h1>{{ $page->title }}</h1>
    <p class="meta">{{ config('app.name') }} — last updated {{ $page->updated_at->format('F j, Y') }}</p>

    {!! str_replace('{{ app_name }}', e(config('app.name')), $page->content_html) !!}
</body>
</html>

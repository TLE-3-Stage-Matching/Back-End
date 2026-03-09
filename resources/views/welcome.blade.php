<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>API Documentation – {{ config('app.name', 'Laravel') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 1.5rem;
            font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif;
            font-size: 15px;
            line-height: 1.6;
            color: #1b1b18;
            background: #fdfdfc;
        }
        @media (prefers-color-scheme: dark) {
            body { background: #0a0a0a; color: #ededec; }
        }
        .api-docs {
            max-width: 52rem;
            margin: 0 auto;
        }
        .api-docs h1 { font-size: 1.75rem; margin: 0 0 0.5rem; font-weight: 600; }
        .api-docs h2 { font-size: 1.35rem; margin: 2rem 0 0.75rem; padding-bottom: 0.25rem; border-bottom: 1px solid #e3e3e0; font-weight: 600; }
        .api-docs h3 { font-size: 1.1rem; margin: 1.5rem 0 0.5rem; font-weight: 600; }
        @media (prefers-color-scheme: dark) {
            .api-docs h2 { border-bottom-color: #3e3e3a; }
        }
        .api-docs p { margin: 0 0 0.75rem; }
        .api-docs ul, .api-docs ol { margin: 0 0 1rem; padding-left: 1.5rem; }
        .api-docs li { margin-bottom: 0.25rem; }
        .api-docs a { color: #c53030; text-decoration: none; }
        .api-docs a:hover { text-decoration: underline; }
        @media (prefers-color-scheme: dark) {
            .api-docs a { color: #f87171; }
        }
        .api-docs hr { border: none; border-top: 1px solid #e3e3e0; margin: 2rem 0; }
        @media (prefers-color-scheme: dark) {
            .api-docs hr { border-top-color: #3e3e3a; }
        }
        .api-docs code {
            font-family: ui-monospace, 'Cascadia Code', 'Source Code Pro', Menlo, monospace;
            font-size: 0.9em;
            padding: 0.15em 0.4em;
            border-radius: 4px;
            background: #eee;
            color: #1b1b18;
        }
        @media (prefers-color-scheme: dark) {
            .api-docs code { background: #2d2d2d; color: #e5e5e5; }
        }
        .api-docs pre {
            margin: 0 0 1rem;
            padding: 1rem;
            overflow-x: auto;
            border-radius: 6px;
            background: #1b1b18;
            color: #e5e5e5;
        }
        .api-docs pre code { padding: 0; background: none; color: inherit; font-size: 0.85rem; }
        @media (prefers-color-scheme: dark) {
            .api-docs pre { background: #161615; }
        }
        .api-docs table {
            width: 100%;
            border-collapse: collapse;
            margin: 0 0 1rem;
            font-size: 0.9rem;
        }
        .api-docs th, .api-docs td {
            border: 1px solid #e3e3e0;
            padding: 0.5rem 0.75rem;
            text-align: left;
        }
        .api-docs th { background: #f5f5f4; font-weight: 600; }
        @media (prefers-color-scheme: dark) {
            .api-docs th, .api-docs td { border-color: #3e3e3a; }
            .api-docs th { background: #262626; }
        }
        .api-docs details { margin: 0.5rem 0 1rem; }
        .api-docs summary {
            cursor: pointer;
            font-weight: 500;
            padding: 0.35rem 0;
            user-select: none;
        }
        .api-docs summary:hover { opacity: 0.9; }
        .api-docs blockquote {
            margin: 0 0 1rem;
            padding-left: 1rem;
            border-left: 4px solid #e3e3e0;
            color: #666;
        }
        @media (prefers-color-scheme: dark) {
            .api-docs blockquote { border-left-color: #3e3e3a; color: #a1a09a; }
        }
    </style>
</head>
<body>
    <div id="api-docs" class="api-docs"></div>
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <script>
        (function () {
            var md = @json($apiDocMarkdown);
            marked.setOptions({ gfm: true, breaks: true });
            document.getElementById('api-docs').innerHTML = marked.parse(md);
        })();
    </script>
</body>
</html>

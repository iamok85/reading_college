<!doctype html>
<html>
    <head>
        <meta charset="utf-8">
        <style>
            body {
                font-family: DejaVu Sans, Arial, sans-serif;
                font-size: 16px;
                line-height: 1.6;
                color: #111827;
            }
            h1 {
                font-size: 22px;
                margin-bottom: 8px;
            }
            .meta {
                font-size: 12px;
                color: #6b7280;
                margin-bottom: 16px;
            }
            .content {
                white-space: pre-wrap;
            }
        </style>
    </head>
    <body>
        <h1>Essay Analysis</h1>
        <div class="meta">Generated at: {{ $generatedAt->format('Y-m-d H:i') }}</div>
        <div class="content">{{ $analysis }}</div>
    </body>
</html>

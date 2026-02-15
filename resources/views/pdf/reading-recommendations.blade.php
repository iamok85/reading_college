<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Readings</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 14px; color: #111; }
        h1 { font-size: 22px; margin-bottom: 10px; }
        .meta { font-size: 12px; color: #666; margin-bottom: 18px; }
        .item { border: 1px solid #ddd; padding: 12px; margin-bottom: 12px; border-radius: 8px; }
        .type { font-size: 11px; color: #555; text-transform: uppercase; letter-spacing: 0.5px; }
        .title { font-size: 16px; font-weight: bold; margin: 6px 0 8px; }
        .paragraph { font-size: 14px; line-height: 1.7; }
    </style>
</head>
<body>
    <h1>Readings</h1>
    <div class="meta">Generated at: {{ $generatedAt->format('Y-m-d H:i') }}</div>

    @if (empty($items))
        <p>No recommendations available.</p>
    @else
        @foreach ($items as $item)
            <div class="item">
                <div class="type">{{ $item['type'] ?? 'Book' }}</div>
                <div class="title">{{ $item['title'] ?? '' }}</div>
                <div class="paragraph">{{ $item['paragraph'] ?? '' }}</div>
            </div>
        @endforeach
    @endif
</body>
</html>

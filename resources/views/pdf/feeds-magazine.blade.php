<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <title>Feeds Magazine</title>
        <style>
            @page { margin: 28px 32px; }
            body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; color: #111827; }
            .masthead { border-bottom: 2px solid #111827; margin-bottom: 16px; padding-bottom: 8px; }
            .title { font-size: 22px; font-weight: bold; letter-spacing: 0.5px; }
            .subtitle { color: #6b7280; margin-top: 2px; font-size: 11px; }
            .issue { font-size: 10px; color: #6b7280; text-transform: uppercase; letter-spacing: 1px; }
            .grid { width: 100%; }
            .item { border: 1px solid #e5e7eb; padding: 12px; margin-bottom: 16px; }
            .meta { font-size: 11px; color: #6b7280; margin-bottom: 8px; }
            .image { width: 500px; height: 500px; object-fit: cover; border: 1px solid #e5e7eb; }
            .text { white-space: pre-wrap; font-size: 12px; margin-top: 8px; line-height: 1.4; }
            .footer { position: fixed; bottom: 10px; left: 32px; right: 32px; font-size: 10px; color: #9ca3af; border-top: 1px solid #e5e7eb; padding-top: 4px; }
            .page-break { page-break-after: always; }
        </style>
    </head>
    <body>
        <div class="masthead">
            <div class="issue">Reading College · Feeds Magazine</div>
            <div class="title">Kids Writing Highlights</div>
            <div class="subtitle">Generated {{ $generatedAt }}</div>
        </div>

        @foreach ($items as $index => $item)
            <div class="item">
                <div class="meta">
                    {{ $item['child_name'] }}
                    @if (!empty($item['child_age']))
                        ({{ $item['child_age'] }})
                    @endif
                    · {{ optional($item['shared_at'])->format('Y-m-d') }}
                </div>
                @if (!empty($item['image_data']))
                    <img class="image" src="{{ $item['image_data'] }}" alt="Shared essay image">
                @endif
                @if (!empty($item['corrected_text']))
                    <div class="text">{{ $item['corrected_text'] }}</div>
                @endif
            </div>
            @if (($index + 1) % 2 === 0)
                <div class="page-break"></div>
            @endif
        @endforeach

        <div class="footer">
            Reading College · Feeds Magazine
        </div>
    </body>
</html>

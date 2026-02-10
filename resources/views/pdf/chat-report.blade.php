<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Essay Report</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; color: #111; }
        h1 { font-size: 18px; margin: 0 0 8px; }
        h2 { font-size: 14px; margin: 16px 0 6px; }
        .meta { font-size: 11px; color: #555; margin-bottom: 10px; }
        .box { border: 1px solid #ddd; padding: 8px; border-radius: 6px; }
        .images { margin-top: 8px; }
        .image { margin-bottom: 10px; }
        .image img { max-width: 100%; height: auto; }
        pre { white-space: pre-wrap; font-family: DejaVu Sans, Arial, sans-serif; }
    </style>
</head>
<body>
    <h1>Essay Report</h1>
    <div class="meta">
        @if ($user)
            {{ $user->name }} &middot;
        @endif
        {{ $generatedAt->format('Y-m-d H:i:s') }}
    </div>

    @if (!empty($images))
        <h2>Images</h2>
        <div class="images">
            @foreach ($images as $img)
                <div class="image">
                    <img src="{{ $img }}" alt="Uploaded image">
                </div>
            @endforeach
        </div>
    @endif

    <h2>Submitted Text</h2>
    <div class="box">
        <pre>{{ $submittedText }}</pre>
    </div>

    <h2>Feedback</h2>
    <div class="box">
        <pre>{{ $responseText }}</pre>
    </div>

</body>
</html>

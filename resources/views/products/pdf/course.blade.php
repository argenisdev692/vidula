{{-- Course PDF for Products module deliverables. --}}
<!DOCTYPE html>
<html lang="{{ $document->language }}">
<head>
    <meta charset="utf-8">
    <title>{{ $document->title }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111; line-height: 1.45; }
        h1 { font-size: 22px; margin-bottom: 8px; }
        h2 { font-size: 16px; margin-top: 22px; border-bottom: 1px solid #ccc; padding-bottom: 4px; }
        h3 { font-size: 14px; margin-top: 16px; }
        h4 { font-size: 12px; margin-top: 12px; color: #333; }
        pre, code { font-family: DejaVu Sans Mono, monospace; font-size: 10px; white-space: pre-wrap; }
        .meta { color: #555; margin-bottom: 18px; }
        .company { font-size: 10px; color: #666; margin-bottom: 24px; }
        .block { margin-bottom: 10px; }
    </style>
</head>
<body>
    @if (!empty($company['name']))
        <div class="company">{{ $company['name'] }}</div>
    @endif

    <h1>{{ $document->title }}</h1>
    <div class="meta">{{ $document->type->value }} · {{ $document->language }}</div>

    @if ($document->description)
        <p>{{ $document->description }}</p>
    @endif

    @foreach ($document->sessions as $session)
        <h2>{{ $session->sessionNumber }}. {{ $session->title }}</h2>

        @foreach ($session->topics as $topic)
            <h3>{{ $topic->sortOrder }}. {{ $topic->title }}</h3>

            @if ($topic->intro)
                <h4>Intro</h4>
                <div class="block">{!! nl2br(e($topic->intro)) !!}</div>
            @endif

            @if ($topic->body)
                <h4>Body</h4>
                <div class="block">{!! nl2br(e($topic->body)) !!}</div>
            @endif

            @if ($topic->outro)
                <h4>Outro</h4>
                <div class="block">{!! nl2br(e($topic->outro)) !!}</div>
            @endif

            @if ($topic->notes)
                <h4>Notes</h4>
                <div class="block">{!! nl2br(e($topic->notes)) !!}</div>
            @endif
        @endforeach
    @endforeach
</body>
</html>

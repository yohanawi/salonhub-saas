<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Rate your visit · {{ $feedback->tenant?->name }}</title>
    <style>
        :root { --bg: #f5f8fa; --card: #fff; --text: #1e1e2d; --muted: #7e8299; --star: #f6c000; --accent: #009ef7; --line: #e4e6ef; }
        @media (prefers-color-scheme: dark) { :root { --bg: #151521; --card: #1e1e2d; --text: #f5f8fa; --muted: #a1a5b7; --line: #2b2b40; } }
        * { box-sizing: border-box; }
        body { margin: 0; background: var(--bg); color: var(--text); font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif; }
        .wrap { max-width: 440px; margin: 0 auto; padding: 32px 16px; }
        .card { background: var(--card); border-radius: 16px; padding: 24px; box-shadow: 0 2px 12px rgba(0,0,0,.05); text-align: center; }
        .muted { color: var(--muted); font-size: 14px; }
        .stars { display: flex; flex-direction: row-reverse; justify-content: center; gap: 6px; margin: 20px 0; }
        .stars input { display: none; }
        .stars label { font-size: 42px; color: var(--line); cursor: pointer; line-height: 1; }
        .stars input:checked ~ label, .stars label:hover, .stars label:hover ~ label { color: var(--star); }
        textarea { width: 100%; min-height: 90px; border: 1px solid var(--line); border-radius: 10px; padding: 10px; font: inherit; background: transparent; color: var(--text); }
        button { width: 100%; margin-top: 14px; padding: 12px; border: 0; border-radius: 10px; background: var(--accent); color: #fff; font-size: 16px; font-weight: 600; cursor: pointer; }
        .error { color: #f1416c; font-size: 14px; margin-bottom: 8px; }
    </style>
</head>

<body>
    <div class="wrap">
        <div class="card">
            <h2 style="margin: 0 0 6px;">{{ $feedback->tenant?->name }}</h2>
            @if ($feedback->submitted_at)
                <div style="font-size: 40px; color: var(--star);">{{ str_repeat('★', (int) $feedback->rating) }}</div>
                <p>Thank you for your feedback!</p>
                <p class="muted">We hope to see you again soon.</p>
            @else
                <p class="muted">
                    How was your visit{{ $feedback->appointment?->starts_at ? ' on ' . $feedback->appointment->starts_at->format('M d') : '' }}{{ $feedback->staff ? ' with ' . $feedback->staff->first_name : '' }}?
                </p>
                <form method="POST" action="{{ $submitUrl }}">
                    @csrf
                    @if ($errors->any())
                        <div class="error">{{ $errors->first() }}</div>
                    @endif
                    <div class="stars">
                        @for ($star = 5; $star >= 1; $star--)
                            <input type="radio" id="star{{ $star }}" name="rating" value="{{ $star }}" @checked((int) old('rating') === $star)>
                            <label for="star{{ $star }}" title="{{ $star }} stars">★</label>
                        @endfor
                    </div>
                    <textarea name="comment" placeholder="Anything you'd like to tell us? (optional)">{{ old('comment') }}</textarea>
                    <button type="submit">Send feedback</button>
                </form>
            @endif
        </div>
    </div>
</body>

</html>

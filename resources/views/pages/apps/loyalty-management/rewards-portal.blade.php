<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $customer->tenant?->name }} Rewards</title>
    <style>
        :root { --bg: #f5f8fa; --card: #ffffff; --text: #1e1e2d; --muted: #7e8299; --accent: #009ef7; --gold: #f6c000; --line: #eff2f5; }
        @media (prefers-color-scheme: dark) { :root { --bg: #151521; --card: #1e1e2d; --text: #f5f8fa; --muted: #a1a5b7; --line: #2b2b40; } }
        * { box-sizing: border-box; }
        body { margin: 0; background: var(--bg); color: var(--text); font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif; }
        .wrap { max-width: 480px; margin: 0 auto; padding: 24px 16px 40px; }
        .card { background: var(--card); border-radius: 16px; padding: 20px; margin-bottom: 16px; box-shadow: 0 2px 12px rgba(0, 0, 0, .05); }
        .muted { color: var(--muted); font-size: 13px; }
        .points { font-size: 44px; font-weight: 800; line-height: 1; margin: 8px 0; }
        .badge { display: inline-block; padding: 4px 12px; border-radius: 999px; font-weight: 700; font-size: 13px; background: var(--gold); color: #1e1e2d; }
        .bar { height: 10px; background: var(--line); border-radius: 999px; overflow: hidden; margin: 10px 0 6px; }
        .bar span { display: block; height: 100%; background: var(--accent); }
        .row { display: flex; justify-content: space-between; gap: 12px; padding: 10px 0; border-bottom: 1px solid var(--line); font-size: 14px; }
        .row:last-child { border-bottom: 0; }
        .plus { color: #50cd89; font-weight: 700; }
        .minus { color: #f1416c; font-weight: 700; }
        h1 { font-size: 20px; margin: 0 0 4px; }
        h2 { font-size: 15px; margin: 0 0 8px; }
    </style>
</head>

<body>
    <div class="wrap">
        <h1>{{ $customer->tenant?->name }}</h1>
        <div class="muted" style="margin-bottom: 16px;">Rewards for {{ $customer->full_name }}</div>

        @if (! $account)
            <div class="card">You are not enrolled in a rewards program yet. Ask us at your next visit!</div>
        @else
            <div class="card">
                <div class="muted">Available points</div>
                <div class="points">{{ number_format((int) $account->available_points) }}</div>
                <div class="muted">Worth {{ $currency }} {{ number_format($pointsValue, 2) }} off your next visit</div>
                @if ($tier)
                    <div style="margin-top: 14px;"><span class="badge">{{ $tier->name }} member</span>
                        @if ((float) $tier->points_multiplier > 1)
                            <span class="muted"> · {{ rtrim(rtrim(number_format((float) $tier->points_multiplier, 2), '0'), '.') }}× points on every visit</span>
                        @endif
                    </div>
                    @if ($tier->benefits)
                        <div class="muted" style="margin-top: 8px;">{{ $tier->benefits }}</div>
                    @endif
                @endif
            </div>

            @if ($nextTier)
                @php
                    $floor = $tier?->min_points ?? 0;
                    $span = max(1, $nextTier->min_points - $floor);
                    $percent = max(0, min(100, (int) floor(((int) $account->lifetime_earned_points - $floor) / $span * 100)));
                @endphp
                <div class="card">
                    <h2>Next: {{ $nextTier->name }}</h2>
                    <div class="bar"><span style="width: {{ $percent }}%"></span></div>
                    <div class="muted">{{ number_format($pointsToNext) }} more points to reach {{ $nextTier->name }}.</div>
                </div>
            @endif

            @if ($membership)
                <div class="card">
                    <h2>{{ $membership->plan?->name }} membership</h2>
                    <div class="muted">Valid until {{ $membership->end_date?->format('M d, Y') }}{{ $membership->auto_renew ? ' · renews automatically' : '' }}</div>
                </div>
            @endif

            <div class="card">
                <h2>Recent activity</h2>
                @forelse ($transactions as $transaction)
                    <div class="row">
                        <div>
                            <div>{{ $transaction->description ?: str($transaction->type)->replace('_', ' ')->headline() }}</div>
                            <div class="muted">{{ $transaction->created_at?->format('M d, Y') }}</div>
                        </div>
                        <div class="{{ $transaction->points >= 0 ? 'plus' : 'minus' }}">{{ $transaction->points >= 0 ? '+' : '' }}{{ number_format($transaction->points) }}</div>
                    </div>
                @empty
                    <div class="muted">No points activity yet.</div>
                @endforelse
            </div>
        @endif

        <div class="muted" style="text-align: center;">This private link expires. Ask the salon for a new one any time.</div>
    </div>
</body>

</html>

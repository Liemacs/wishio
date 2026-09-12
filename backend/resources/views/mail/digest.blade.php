{{-- Email HTML: stiluri inline, tabele pentru layout. Clienții de email nu
     suportă flexbox, grid sau CSS extern. --}}
<!DOCTYPE html>
<html lang="{{ $locale }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ trans_choice('wishio.digest.subject', $entries->count(), ['count' => $entries->count()], $locale) }}</title>
</head>
<body style="margin:0;padding:0;background:#fafafa;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif;color:#18181b;">

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#fafafa;padding:24px 12px;">
<tr><td align="center">

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:520px;background:#ffffff;border-radius:20px;overflow:hidden;">

        <tr>
            <td {{-- Outlook ignoră gradientele: culoarea solidă e rezerva, nu decor. --}}
                style="background-color:#fff1f2;background:linear-gradient(135deg,#fff1f2,#ffffff);padding:28px 28px 22px;">
                <div style="font-size:13px;font-weight:700;letter-spacing:.08em;color:#e11d48;">WISHIO</div>
                <div style="margin-top:14px;font-size:22px;font-weight:700;line-height:1.3;">
                    {{ __('wishio.digest.greeting', ['name' => $user->name], $locale) }}
                </div>
                <div style="margin-top:6px;font-size:15px;line-height:1.5;color:#52525b;">
                    {{ __('wishio.digest.intro', [], $locale) }}
                </div>
            </td>
        </tr>

        <tr>
            <td style="padding:8px 16px 4px;">
                @foreach ($entries as $entry)
                    @php
                        $when = match (true) {
                            $entry->daysUntil === 0 => __('wishio.digest.today', [], $locale),
                            $entry->daysUntil === 1 => __('wishio.digest.tomorrow', [], $locale),
                            default => trans_choice('wishio.digest.in_days', $entry->daysUntil, ['count' => $entry->daysUntil], $locale),
                        };
                        $dot = ['birthday' => '#f43f5e', 'name_day' => '#8b5cf6',
                                'holiday' => '#f59e0b', 'anniversary' => '#ec4899'][$entry->type] ?? '#a1a1aa';
                    @endphp

                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:8px 0;">
                    <tr>
                        <td width="10" valign="top" style="padding-top:6px;">
                            <div style="width:8px;height:8px;border-radius:8px;background:{{ $dot }};"></div>
                        </td>
                        <td style="padding-left:10px;">
                            <div style="font-size:16px;font-weight:600;">{{ $entry->title }}</div>
                            <div style="margin-top:2px;font-size:14px;color:#71717a;">
                                {{ $entry->subtitle }} · {{ $when }}
                            </div>
                        </td>
                    </tr>
                    </table>
                @endforeach
            </td>
        </tr>

        <tr>
            <td style="padding:16px 28px 30px;">
                <a href="{{ config('app.url') }}"
                   style="display:block;background:#e11d48;color:#ffffff;text-decoration:none;
                          text-align:center;padding:14px 20px;border-radius:14px;font-size:16px;font-weight:600;">
                    {{ __('wishio.digest.cta', [], $locale) }}
                </a>
            </td>
        </tr>
    </table>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:520px;">
    <tr><td style="padding:18px 28px;text-align:center;font-size:12px;line-height:1.6;color:#a1a1aa;">
        {{ __('wishio.digest.footer', [], $locale) }}<br>
        <a href="{{ $unsubscribeUrl }}" style="color:#a1a1aa;">{{ __('wishio.digest.unsubscribe', [], $locale) }}</a>
    </td></tr>
    </table>

</td></tr>
</table>

</body>
</html>

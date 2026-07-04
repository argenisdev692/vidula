@php
    // Brand palette (inlined — email clients do not support CSS variables).
    $cInk = '#090d1c';
    $cBase = '#0f1730';
    $cSurface = '#141d38';
    $cOverlay = '#1b2647';
    $cText = '#eef1fb';
    $cSecondary = '#b4bad2';
    $cMuted = '#868ca8';
    $cBorder = 'rgba(255,255,255,0.10)';
    $cAccent = '#7c3aed';
    $gradient = 'linear-gradient(120deg, #7c3aed 0%, #4f46e5 100%)';
    $font = "'Exo', 'Segoe UI', ui-sans-serif, system-ui, Arial, sans-serif";

    // DB-first company branding (App\Models\CompanyData), config/env fallback.
    $profile = \Shared\Infrastructure\Company\CompanyProfile::data();
    $company = $profile['name'];
    $logo = $profile['logo_url'];
    $companyUrl = $profile['url'];
    $address = $profile['address'];
    $support = $profile['support_email'];

    $socials = collect($profile['socials']);
    $socialMeta = [
        'linkedin'  => ['color' => '#0a66c2', 'label' => 'LinkedIn',  'path' => 'M20.45 20.45h-3.56v-5.57c0-1.33-.02-3.04-1.85-3.04-1.85 0-2.14 1.45-2.14 2.94v5.67H9.34V9h3.42v1.56h.05c.48-.9 1.64-1.85 3.37-1.85 3.6 0 4.27 2.37 4.27 5.46v6.28zM5.34 7.43a2.07 2.07 0 1 1 0-4.14 2.07 2.07 0 0 1 0 4.14zM7.12 20.45H3.55V9h3.57v11.45zM22.22 0H1.77C.79 0 0 .77 0 1.73v20.54C0 23.22.79 24 1.77 24h20.45c.98 0 1.78-.78 1.78-1.73V1.73C24 .77 23.2 0 22.22 0z'],
        'twitter'   => ['color' => '#1d9bf0', 'label' => 'Twitter/X', 'path' => 'M18.9 1.5h3.68l-8.04 9.19L24 22.5h-7.4l-5.8-7.58-6.64 7.58H.48l8.6-9.83L0 1.5h7.59l5.24 6.93L18.9 1.5zm-1.3 18.8h2.04L6.49 3.6H4.3L17.6 20.3z'],
        'instagram' => ['color' => '#e1306c', 'label' => 'Instagram', 'path' => 'M12 2.16c3.2 0 3.58.01 4.85.07 1.17.05 1.8.25 2.23.41.56.22.96.48 1.38.9.42.42.68.82.9 1.38.16.42.36 1.06.41 2.23.06 1.27.07 1.65.07 4.85s-.01 3.58-.07 4.85c-.05 1.17-.25 1.8-.41 2.23-.22.56-.48.96-.9 1.38-.42.42-.82.68-1.38.9-.42.16-1.06.36-2.23.41-1.27.06-1.65.07-4.85.07s-3.58-.01-4.85-.07c-1.17-.05-1.8-.25-2.23-.41-.56-.22-.96-.48-1.38-.9-.42-.42-.68-.82-.9-1.38-.16-.42-.36-1.06-.41-2.23-.06-1.27-.07-1.65-.07-4.85s.01-3.58.07-4.85c.05-1.17.25-1.8.41-2.23.22-.56.48-.96.9-1.38.42-.42.82-.68 1.38-.9.42-.16 1.06-.36 2.23-.41 1.27-.06 1.65-.07 4.85-.07zM12 0C8.74 0 8.33.01 7.05.07 5.78.13 4.9.33 4.14.63c-.79.31-1.46.72-2.13 1.38C1.35 2.68.94 3.35.63 4.14.33 4.9.13 5.78.07 7.05.01 8.33 0 8.74 0 12s.01 3.67.07 4.95c.06 1.27.26 2.15.56 2.91.31.79.72 1.46 1.38 2.13.67.66 1.34 1.07 2.13 1.38.76.3 1.64.5 2.91.56C8.33 23.99 8.74 24 12 24s3.67-.01 4.95-.07c1.27-.06 2.15-.26 2.91-.56.79-.31 1.46-.72 2.13-1.38.66-.67 1.07-1.34 1.38-2.13.3-.76.5-1.64.56-2.91.06-1.28.07-1.69.07-4.95s-.01-3.67-.07-4.95c-.06-1.27-.26-2.15-.56-2.91-.31-.79-.72-1.46-1.38-2.13-.67-.66-1.34-1.07-2.13-1.38-.76-.3-1.64-.5-2.91-.56C15.67.01 15.26 0 12 0zm0 5.84a6.16 6.16 0 1 0 0 12.32 6.16 6.16 0 0 0 0-12.32zm0 10.16a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm6.41-10.4a1.44 1.44 0 1 0 0 2.88 1.44 1.44 0 0 0 0-2.88z'],
        'facebook'  => ['color' => '#1877f2', 'label' => 'Facebook',  'path' => 'M24 12.07C24 5.4 18.63 0 12 0S0 5.4 0 12.07c0 6.02 4.39 11.01 10.13 11.93v-8.44H7.08v-3.49h3.05V9.41c0-3.02 1.79-4.69 4.53-4.69 1.31 0 2.68.24 2.68.24v2.97h-1.5c-1.49 0-1.96.93-1.96 1.89v2.25h3.33l-.53 3.49h-2.8v8.44C19.61 23.08 24 18.09 24 12.07z'],
        'tiktok'    => ['color' => '#25f4ee', 'label' => 'TikTok',    'path' => 'M12.53.02C13.84 0 15.14.01 16.44 0c.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z'],
    ];
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="dark">
    <title>{{ $company }}</title>
</head>
<body style="margin:0; padding:0; background-color:{{ $cInk }}; font-family:{{ $font }};">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:{{ $cInk }}; padding:32px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="width:600px; max-width:600px; background-color:{{ $cSurface }}; border:1px solid {{ $cBorder }}; border-radius:16px; overflow:hidden;">

                    {{-- Header --}}
                    <tr>
                        <td style="background:{{ $gradient }}; padding:28px 32px; text-align:center;">
                            <a href="{{ $companyUrl }}" style="text-decoration:none;">
                                @if($logo)
                                    <img src="{{ $logo }}" alt="{{ $company }}" height="40" style="height:40px; display:inline-block;">
                                @else
                                    <span style="color:#ffffff; font-size:24px; font-weight:700; letter-spacing:0.5px;">{{ $company }}</span>
                                @endif
                            </a>
                        </td>
                    </tr>

                    {{-- Body (white content area for readability) --}}
                    <tr>
                        <td style="background-color:#ffffff; padding:36px 32px; color:#374151; font-size:16px; line-height:1.6;">
                            @yield('content')
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="background-color:{{ $cBase }}; border-top:1px solid {{ $cBorder }}; padding:28px 32px; text-align:center;">
                            @if($socials->isNotEmpty())
                                <table role="presentation" cellpadding="0" cellspacing="0" align="center" style="margin:0 auto 18px;">
                                    <tr>
                                        @foreach($socials as $key => $url)
                                            @php $meta = $socialMeta[$key] ?? null; @endphp
                                            @if($meta)
                                                <td style="padding:0 6px;">
                                                    <a href="{{ $url }}" title="{{ $meta['label'] }}" style="display:inline-block; width:34px; height:34px; background-color:{{ $meta['color'] }}; border-radius:50%; text-align:center; line-height:34px;">
                                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="#ffffff" style="vertical-align:middle;" aria-hidden="true"><path d="{{ $meta['path'] }}"/></svg>
                                                    </a>
                                                </td>
                                            @endif
                                        @endforeach
                                    </tr>
                                </table>
                            @endif

                            <p style="margin:0 0 6px; color:{{ $cSecondary }}; font-size:13px;">
                                &copy; {{ date('Y') }} {{ $company }}. {{ __('All rights reserved.') }}
                            </p>
                            @if($address)
                                <p style="margin:0 0 6px; color:{{ $cMuted }}; font-size:12px;">{{ $address }}</p>
                            @endif
                            <p style="margin:0; color:{{ $cMuted }}; font-size:12px;">
                                {{ __('Need help?') }} <a href="mailto:{{ $support }}" style="color:{{ $cAccent }}; text-decoration:none;">{{ $support }}</a>
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>

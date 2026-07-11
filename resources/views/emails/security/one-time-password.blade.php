@extends('emails.layout')

@section('preheader'){{ __('Your one-time verification code is inside.') }}@endsection

@section('content')
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
        <tr>
            <td style="background-color:#eef2ff; border:1px solid #6366f1; border-radius:12px; padding:16px 18px;">
                <span style="display:inline-block; color:#4338ca; font-size:14px; font-weight:700; text-transform:uppercase; letter-spacing:0.5px;">
                    &#128273;&nbsp; {{ __('Verification code') }}
                </span>
            </td>
        </tr>
    </table>

    <h1 class="email-h1" style="margin:0 0 16px; color:#0f1730; font-size:22px; font-weight:700; line-height:1.3;">
        {{ __('Your one-time code') }}
    </h1>

    <p style="margin:0 0 20px; color:#4b5563;">
        {{ __('Use the code below to continue. Enter it on the screen where you requested it.') }}
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 24px;">
        <tr>
            <td align="center" style="background-color:#f8fafc; border:1px solid #e5e7eb; border-radius:12px; padding:24px 16px;">
                <span style="display:inline-block; color:#0f1730; font-size:38px; font-weight:800; letter-spacing:10px; font-family:'Courier New', ui-monospace, monospace;">
                    {{ $code }}
                </span>
            </td>
        </tr>
    </table>

    @if(!empty($minutes))
        <p style="margin:0 0 16px; color:#4b5563;">
            {{ __('This code expires in :minutes minutes.', ['minutes' => $minutes]) }}
        </p>
    @endif

    <p style="margin:0 0 8px; color:#4b5563;">
        {{ __('For your security, never share this code with anyone. We will never ask you for it.') }}
    </p>

    <p style="margin:0; color:#6b7280; font-size:13px;">
        {{ __('If you did not request this code, you can safely ignore this email.') }}
    </p>
@endsection

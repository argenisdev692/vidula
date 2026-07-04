@extends('emails.layout')

@section('content')
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
        <tr>
            <td style="background-color:#fff7ed; border:1px solid #f59e0b; border-radius:12px; padding:16px 18px;">
                <span style="display:inline-block; color:#b45309; font-size:14px; font-weight:700; text-transform:uppercase; letter-spacing:0.5px;">
                    &#9888;&nbsp; {{ __('Security alert') }}
                </span>
            </td>
        </tr>
    </table>

    <h1 style="margin:0 0 16px; color:#0f1730; font-size:22px; font-weight:700;">
        {{ __('Multiple failed sign-in attempts') }}
    </h1>

    <p style="margin:0 0 16px; color:#4b5563;">
        {{ __('We detected :attempts consecutive failed sign-in attempts on your account. As a precaution, it has been temporarily locked for :minutes minutes.', ['attempts' => $attempts, 'minutes' => $lockMinutes]) }}
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 24px; background-color:#f8fafc; border:1px solid #e5e7eb; border-radius:12px;">
        <tr>
            <td style="padding:12px 16px; color:#6b7280; font-size:13px;">{{ __('IP address') }}</td>
            <td style="padding:12px 16px; color:#111827; font-size:13px; text-align:right;">{{ $ipAddress ?? __('unknown') }}</td>
        </tr>
        <tr>
            <td style="padding:12px 16px; color:#6b7280; font-size:13px; border-top:1px solid #eef0f4;">{{ __('When') }}</td>
            <td style="padding:12px 16px; color:#111827; font-size:13px; text-align:right; border-top:1px solid #eef0f4;">{{ $occurredAt }}</td>
        </tr>
    </table>

    <p style="margin:0 0 24px; color:#4b5563;">
        {{ __('If this was you, you can sign in again once the lock expires. If you did not attempt to sign in, please reset your password immediately.') }}
    </p>

    @if(!empty($resetUrl))
        <table role="presentation" cellpadding="0" cellspacing="0">
            <tr>
                <td style="background:linear-gradient(120deg,#7c3aed 0%,#4f46e5 100%); border-radius:8px;">
                    <a href="{{ $resetUrl }}" style="display:inline-block; padding:13px 26px; color:#ffffff; font-size:15px; font-weight:600; text-decoration:none;">
                        {{ __('Reset my password') }}
                    </a>
                </td>
            </tr>
        </table>
    @endif
@endsection

@extends('emails.layout')

@section('content')
    <h1 style="margin:0 0 16px; color:#0f1730; font-size:22px; font-weight:700;">
        {{ __('Welcome!') }}
    </h1>

    <p style="margin:0 0 16px; color:#4b5563;">
        {{ __('An administrator has created an account for you on :app.', ['app' => $company ?? config('app.name')]) }}
    </p>

    <p style="margin:0 0 24px; color:#4b5563;">
        {{ __('Click the button below to set your password and activate your account.') }}
    </p>

    {{-- CTA button (table-based; solid fallback under the gradient for older clients). --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 24px;">
        <tr>
            <td align="center">
                <a href="{{ $activationUrl }}"
                   style="display:inline-block; padding:14px 32px; background-color:#4f46e5; background:linear-gradient(120deg, #7c3aed 0%, #4f46e5 100%); color:#ffffff; font-size:16px; font-weight:700; text-decoration:none; border-radius:10px;">
                    {{ __('Activate account') }}
                </a>
            </td>
        </tr>
    </table>

    <p style="margin:0 0 16px; color:#4b5563;">
        {{ __('This invitation link expires in :hours hours.', ['hours' => $expiresInHours]) }}
    </p>

    {{-- Fallback for clients that strip the button. --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 24px; background-color:#f8fafc; border:1px solid #e5e7eb; border-radius:12px;">
        <tr>
            <td style="padding:16px 18px;">
                <p style="margin:0 0 6px; color:#6b7280; font-size:13px;">{{ __('Or copy and paste this link into your browser:') }}</p>
                <a href="{{ $activationUrl }}" style="color:#4f46e5; font-size:13px; word-break:break-all; text-decoration:none;">{{ $activationUrl }}</a>
            </td>
        </tr>
    </table>

    <p style="margin:0; color:#6b7280; font-size:13px;">
        {{ __('If you were not expecting this invitation, you can safely ignore this email.') }}
    </p>
@endsection

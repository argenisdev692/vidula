import * as React from 'react';
import { Head, router } from '@inertiajs/react';
import AuthLayout from '@/pages/layouts/AuthLayout';
import { AuthInput } from './components/AuthInput';
import { OtpInput } from './components/OtpInput';
import {
  validateLoginPassword,
  validateOtpRequest,
  validateOtpCode,
} from '@/modules/auth/helpers/validators';
import type { AuthMode, FormStatus } from '@/types/auth';



/** ── Mode Toggle ── */
function ModeToggle({
  mode,
  onToggle,
}: {
  mode: AuthMode;
  onToggle: (m: AuthMode) => void;
}): React.JSX.Element {
  return (
    <div
      className="flex rounded-lg p-1"
      style={{ background: 'color-mix(in srgb, var(--color-white) 6%, transparent)' }}
      role="tablist"
      aria-label="Login method"
    >
      {(['password', 'otp'] as const).map((m) => (
        <button
          key={m}
          role="tab"
          aria-selected={mode === m}
          onClick={() => onToggle(m)}
          className="flex-1 cursor-pointer rounded-md px-4 py-2 text-xs font-semibold uppercase tracking-wider transition-all duration-200"
          style={{
            background: mode === m ? 'var(--purple-500)' : 'transparent',
            color: mode === m ? '#ffffff' : 'var(--text-muted)',
          }}
        >
          {m === 'password' ? 'Password' : 'OTP Code'}
        </button>
      ))}
    </div>
  );
}

/** ── Password Eye Toggle ── */
function EyeToggle({
  show,
  onToggle,
}: {
  show: boolean;
  onToggle: () => void;
}): React.JSX.Element {
  return (
    <button
      type="button"
      onClick={onToggle}
      className="text-(--text-muted) cursor-pointer transition-colors hover:text-(--purple-500)"
      aria-label={show ? 'Hide password' : 'Show password'}
    >
      {show ? (
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
          <path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94" />
          <path d="M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19" />
          <line x1="1" y1="1" x2="23" y2="23" />
        </svg>
      ) : (
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
          <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
          <circle cx="12" cy="12" r="3" />
        </svg>
      )}
    </button>
  );
}

/** ── Main Login Page ── */
export default function LoginPage(): React.JSX.Element {
  const [mode, setMode] = React.useState<AuthMode>('password');
  const [status, setStatus] = React.useState<FormStatus>('idle');
  const [errors, setErrors] = React.useState<Record<string, string>>({});
  const [serverError, setServerError] = React.useState('');

  // Password mode state
  const [email, setEmail] = React.useState('');
  const [password, setPassword] = React.useState('');
  const [showPassword, setShowPassword] = React.useState(false);

  // OTP mode state
  const [identifier, setIdentifier] = React.useState('');
  const [otpSent, setOtpSent] = React.useState(false);
  const [otpCode, setOtpCode] = React.useState('');
  const [resendTimer, setResendTimer] = React.useState(0);



  // Resend timer countdown
  React.useEffect(() => {
    if (resendTimer <= 0) return;
    const interval = setInterval(() => {
      setResendTimer((prev) => (prev <= 1 ? 0 : prev - 1));
    }, 1000);
    return () => clearInterval(interval);
  }, [resendTimer]);

  // Clear errors when switching mode
  React.useEffect(() => {
    setErrors({});
    setServerError('');
    setStatus('idle');
  }, [mode]);

  /** ── Password Submit ── */
  function handlePasswordSubmit(e: React.FormEvent): void {
    e.preventDefault();
    const validation = validateLoginPassword(email, password);
    if (!validation.valid) {
      setErrors(validation.errors);
      return;
    }
    setErrors({});
    setStatus('loading');
    setServerError('');

    router.post(
      '/login',
      { email, password },
      {
        onFinish: () => setStatus('idle'),
        onError: (errs) => {
          setStatus('error');
          if (errs.email) setServerError(errs.email);
          else setServerError('Invalid credentials. Please try again.');
        },
      },
    );
  }

  /** ── OTP Request ── */
  function handleOtpRequest(e: React.FormEvent): void {
    e.preventDefault();
    const validation = validateOtpRequest(identifier);
    if (!validation.valid) {
      setErrors(validation.errors);
      return;
    }
    setErrors({});
    setStatus('loading');
    setServerError('');

    router.post(
      '/login/otp/send',
      { identifier },
      {
        onSuccess: () => {
          setOtpSent(true);
          setResendTimer(60);
          setStatus('idle');
        },
        onError: (errs) => {
          setStatus('error');
          setServerError(errs.identifier ?? 'Failed to send OTP. Please try again.');
        },
        onFinish: () => {
          if (status === 'loading') setStatus('idle');
        },
      },
    );
  }

  /** ── OTP Verify ── */
  function handleOtpVerify(e: React.FormEvent): void {
    e.preventDefault();
    const validation = validateOtpCode(otpCode);
    if (!validation.valid) {
      setErrors(validation.errors);
      return;
    }
    setErrors({});
    setStatus('loading');
    setServerError('');

    router.post(
      '/login/otp/verify',
      { identifier, otp: otpCode },
      {
        onFinish: () => setStatus('idle'),
        onError: (errs) => {
          setStatus('error');
          setServerError(errs.otp ?? 'Invalid code. Please try again.');
        },
      },
    );
  }

  /** ── Resend OTP ── */
  function handleResend(): void {
    setOtpCode('');
    setServerError('');
    setStatus('loading');

    router.post(
      '/login/otp/send',
      { identifier },
      {
        onSuccess: () => {
          setResendTimer(60);
          setStatus('idle');
        },
        onError: () => {
          setStatus('error');
          setServerError('Failed to resend code.');
        },
      },
    );
  }

  return (
    <>
      <Head title="Sign In — Vidula" />
      <AuthLayout>
        {/* Title */}
        <div className="mb-6 text-center">
          <h2
            className="text-2xl font-bold tracking-tight"
            style={{ color: 'var(--color-white)' }}
          >
            Welcome back
          </h2>
          <p
            className="mt-1 text-sm"
            style={{ color: 'var(--text-muted)' }}
          >
            Sign in to your account
          </p>
        </div>

        {/* Mode Toggle */}
        <div className="mb-6">
          <ModeToggle mode={mode} onToggle={setMode} />
        </div>

        {/* Server Error */}
        {serverError && (
          <div
            className="mb-4 flex items-center gap-2 rounded-lg px-4 py-3 text-sm"
            style={{
              background: 'color-mix(in srgb, var(--accent-error) 10%, transparent)',
              border: '1px solid color-mix(in srgb, var(--accent-error) 25%, transparent)',
              color: 'var(--accent-error)',
            }}
            role="alert"
          >
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
              <circle cx="12" cy="12" r="10" />
              <line x1="15" y1="9" x2="9" y2="15" />
              <line x1="9" y1="9" x2="15" y2="15" />
            </svg>
            {serverError}
          </div>
        )}

        {/* ══ PASSWORD MODE ══ */}
        {mode === 'password' && (
          <form onSubmit={handlePasswordSubmit} className="space-y-4" noValidate>
            <AuthInput
              label="Email"
              type="email"
              placeholder="you@example.com"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              error={errors.email}
              autoComplete="email"
              disabled={status === 'loading'}
            />

            <AuthInput
              label="Password"
              type={showPassword ? 'text' : 'password'}
              placeholder="••••••••"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              error={errors.password}
              autoComplete="current-password"
              disabled={status === 'loading'}
              rightElement={
                <EyeToggle
                  show={showPassword}
                  onToggle={() => setShowPassword(!showPassword)}
                />
              }
            />



            {/* Forgot password link */}
            <div className="text-right">
              <button
                type="button"
                onClick={() => router.visit('/forgot-password')}
                className="text-xs font-medium cursor-pointer transition-colors hover:underline"
                style={{ color: 'var(--purple-500)' }}
              >
                Forgot password?
              </button>
            </div>

            <button
              type="submit"
              disabled={status === 'loading'}
              className="flex h-11 w-full cursor-pointer items-center justify-center rounded-lg text-sm font-semibold transition-all duration-200 hover:brightness-110 disabled:cursor-not-allowed disabled:opacity-60"
              style={{
                background: 'var(--grad-primary)',
                color: '#ffffff',
                boxShadow: '0 4px 16px color-mix(in srgb, var(--purple-500) 30%, transparent)',
              }}
            >
              {status === 'loading' ? (
                <svg className="h-5 w-5 animate-spin" viewBox="0 0 24 24" fill="none">
                  <circle cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="3" strokeDasharray="60" strokeLinecap="round" />
                </svg>
              ) : (
                'Sign In'
              )}
            </button>
          </form>
        )}

        {/* ══ OTP MODE ══ */}
        {mode === 'otp' && !otpSent && (
          <form onSubmit={handleOtpRequest} className="space-y-4" noValidate>
            <AuthInput
              label="Email"
              type="email"
              placeholder="you@example.com"
              value={identifier}
              onChange={(e) => setIdentifier(e.target.value)}
              error={errors.identifier}
              autoComplete="email"
              disabled={status === 'loading'}
            />

            <button
              type="submit"
              disabled={status === 'loading'}
              className="flex h-11 w-full cursor-pointer items-center justify-center rounded-lg text-sm font-semibold transition-all duration-200 hover:brightness-110 disabled:cursor-not-allowed disabled:opacity-60"
              style={{
                background: 'var(--grad-primary)',
                color: '#ffffff',
                boxShadow: '0 4px 16px color-mix(in srgb, var(--purple-500) 30%, transparent)',
              }}
            >
              {status === 'loading' ? (
                <svg className="h-5 w-5 animate-spin" viewBox="0 0 24 24" fill="none">
                  <circle cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="3" strokeDasharray="60" strokeLinecap="round" />
                </svg>
              ) : (
                'Send OTP Code'
              )}
            </button>
          </form>
        )}

        {/* ══ OTP VERIFICATION ══ */}
        {mode === 'otp' && otpSent && (
          <form onSubmit={handleOtpVerify} className="space-y-6" noValidate>
            <div className="text-center">
              <div
                className="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full"
                style={{ background: 'color-mix(in srgb, var(--purple-500) 15%, transparent)' }}
              >
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--purple-500)" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                  <rect x="2" y="4" width="20" height="16" rx="2" />
                  <path d="M22 7l-8.97 5.7a1.94 1.94 0 01-2.06 0L2 7" />
                </svg>
              </div>
              <p className="text-sm" style={{ color: 'var(--text-secondary)' }}>
                We sent a 6-digit code to
              </p>
              <p className="mt-1 text-sm font-semibold" style={{ color: 'var(--purple-500)' }}>
                {identifier}
              </p>
            </div>

            <OtpInput
              value={otpCode}
              onChange={setOtpCode}
              disabled={status === 'loading'}
              hasError={!!errors.otp}
            />

            {errors.otp && (
              <p
                className="text-center text-xs font-medium"
                style={{ color: 'var(--accent-error)' }}
                role="alert"
              >
                {errors.otp}
              </p>
            )}

            <button
              type="submit"
              disabled={status === 'loading'}
              className="flex h-11 w-full cursor-pointer items-center justify-center rounded-lg text-sm font-semibold transition-all duration-200 hover:brightness-110 disabled:cursor-not-allowed disabled:opacity-60"
              style={{
                background: 'var(--grad-primary)',
                color: '#ffffff',
                boxShadow: '0 4px 16px color-mix(in srgb, var(--purple-500) 30%, transparent)',
              }}
            >
              {status === 'loading' ? (
                <svg className="h-5 w-5 animate-spin" viewBox="0 0 24 24" fill="none">
                  <circle cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="3" strokeDasharray="60" strokeLinecap="round" />
                </svg>
              ) : (
                'Verify Code'
              )}
            </button>

            {/* Resend / Back */}
            <div className="flex items-center justify-between text-xs">
              <button
                type="button"
                onClick={() => {
                  setOtpSent(false);
                  setOtpCode('');
                  setErrors({});
                  setServerError('');
                }}
                className="font-medium cursor-pointer transition-colors hover:underline"
                style={{ color: 'var(--text-muted)' }}
              >
                ← Change email
              </button>

              {resendTimer > 0 ? (
                <span style={{ color: 'var(--text-muted)' }}>
                  Resend in {resendTimer}s
                </span>
              ) : (
                <button
                  type="button"
                  onClick={handleResend}
                  disabled={status === 'loading'}
                  className="font-medium cursor-pointer transition-colors hover:underline"
                  style={{ color: 'var(--purple-500)' }}
                >
                  Resend code
                </button>
              )}
            </div>
          </form>
        )}
      </AuthLayout>
    </>
  );
}

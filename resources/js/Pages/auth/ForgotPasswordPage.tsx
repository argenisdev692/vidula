import * as React from 'react';
import { Head, router } from '@inertiajs/react';
import AuthLayout from '@/pages/layouts/AuthLayout';
import { AuthInput } from './components/AuthInput';
import { OtpInput } from './components/OtpInput';
import { PasswordStrengthBar } from './components/PasswordStrengthBar';
import {
  validateForgotPasswordEmail,
  validateOtpCode,
  validateNewPassword,
} from '@/modules/auth/helpers/validators';
import type { ForgotPasswordStep, FormStatus } from '@/types/auth';

export default function ForgotPasswordPage(): React.JSX.Element {
  const [step, setStep] = React.useState<ForgotPasswordStep>('email');
  const [status, setStatus] = React.useState<FormStatus>('idle');
  const [errors, setErrors] = React.useState<Record<string, string>>({});
  const [serverError, setServerError] = React.useState('');

  // Step 1: Email
  const [email, setEmail] = React.useState('');

  // Step 2: OTP
  const [otpCode, setOtpCode] = React.useState('');
  const [resetToken, setResetToken] = React.useState('');
  const [resendTimer, setResendTimer] = React.useState(0);

  // Step 3: New password
  const [password, setPassword] = React.useState('');
  const [passwordConfirm, setPasswordConfirm] = React.useState('');
  const [showPassword, setShowPassword] = React.useState(false);
  const [showConfirm, setShowConfirm] = React.useState(false);

  // Timer for resend
  React.useEffect(() => {
    if (resendTimer <= 0) return;
    const interval = setInterval(() => {
      setResendTimer((prev) => (prev <= 1 ? 0 : prev - 1));
    }, 1000);
    return () => clearInterval(interval);
  }, [resendTimer]);

  /** ── Step 1: Send reset email ── */
  function handleSendEmail(e: React.FormEvent): void {
    e.preventDefault();
    const validation = validateForgotPasswordEmail(email);
    if (!validation.valid) {
      setErrors(validation.errors);
      return;
    }
    setErrors({});
    setStatus('loading');
    setServerError('');

    router.post(
      '/forgot-password',
      { email },
      {
        onSuccess: () => {
          setStep('otp');
          setResendTimer(60);
          setStatus('idle');
        },
        onError: (errs) => {
          setStatus('error');
          setServerError(errs.email ?? 'Failed to send reset email.');
        },
        onFinish: () => {
          if (status === 'loading') setStatus('idle');
        },
      },
    );
  }

  /** ── Step 2: Verify OTP ── */
  function handleVerifyOtp(e: React.FormEvent): void {
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
      '/forgot-password/verify',
      { email, otp: otpCode },
      {
        onSuccess: (page) => {
          const props = page.props as Record<string, unknown>;
          if (props.token && typeof props.token === 'string') {
            setResetToken(props.token);
          }
          setStep('reset');
          setStatus('idle');
        },
        onError: (errs) => {
          setStatus('error');
          setServerError(errs.otp ?? 'Invalid or expired code.');
        },
        onFinish: () => {
          if (status === 'loading') setStatus('idle');
        },
      },
    );
  }

  /** ── Step 3: Reset password ── */
  function handleResetPassword(e: React.FormEvent): void {
    e.preventDefault();
    const validation = validateNewPassword(password, passwordConfirm);
    if (!validation.valid) {
      setErrors(validation.errors);
      return;
    }
    setErrors({});
    setStatus('loading');
    setServerError('');

    router.post(
      '/reset-password',
      {
        email,
        token: resetToken,
        password,
        password_confirmation: passwordConfirm,
      },
      {
        onSuccess: () => {
          setStatus('success');
          // Redirect to login after brief delay
          setTimeout(() => router.visit('/login'), 2000);
        },
        onError: (errs) => {
          setStatus('error');
          setServerError(errs.password ?? errs.email ?? 'Password reset failed.');
        },
        onFinish: () => {
          if (status === 'loading') setStatus('idle');
        },
      },
    );
  }

  /** ── Resend code ── */
  function handleResend(): void {
    setOtpCode('');
    setServerError('');
    setStatus('loading');

    router.post(
      '/forgot-password',
      { email },
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

  /** ── Eye toggle ── */
  function EyeToggle({ show, onToggle }: { show: boolean; onToggle: () => void }): React.JSX.Element {
    return (
      <button
        type="button"
        onClick={onToggle}
        className="text-(--text-muted) transition-colors hover:text-(--color-aqua)"
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

  /** ── Step indicator ── */
  function StepIndicator(): React.JSX.Element {
    const steps = [
      { key: 'email', label: 'Email' },
      { key: 'otp', label: 'Verify' },
      { key: 'reset', label: 'Reset' },
    ] as const;
    const currentIdx = steps.findIndex((s) => s.key === step);

    return (
      <div className="mb-6 flex items-center justify-center gap-2">
        {steps.map((s, i) => (
          <React.Fragment key={s.key}>
            <div className="flex items-center gap-1.5">
              <div
                className="flex h-7 w-7 items-center justify-center rounded-full text-xs font-bold transition-all duration-300"
                style={{
                  background: i <= currentIdx ? 'var(--color-aqua)' : 'color-mix(in srgb, var(--color-white) 8%, transparent)',
                  color: i <= currentIdx ? 'var(--color-navy-dark)' : 'var(--text-disabled)',
                }}
              >
                {i + 1}
              </div>
              <span
                className="hidden text-xs font-medium sm:inline"
                style={{
                  color: i <= currentIdx ? 'var(--color-aqua)' : 'var(--text-disabled)',
                }}
              >
                {s.label}
              </span>
            </div>
            {i < steps.length - 1 && (
              <div
                className="h-px w-8 transition-all duration-300"
                style={{
                  background: i < currentIdx ? 'var(--color-aqua)' : 'color-mix(in srgb, var(--color-white) 10%, transparent)',
                }}
              />
            )}
          </React.Fragment>
        ))}
      </div>
    );
  }

  return (
    <>
      <Head title="Forgot Password — Vidula" />
      <AuthLayout>
        <div className="mb-6 text-center">
          <h2 className="text-xl font-bold" style={{ color: 'var(--color-white)' }}>
            Reset Password
          </h2>
          <p className="mt-1 text-sm" style={{ color: 'var(--text-muted)' }}>
            {step === 'email' && "Enter your email to receive a reset code"}
            {step === 'otp' && "Enter the verification code we sent you"}
            {step === 'reset' && "Choose a new secure password"}
          </p>
        </div>

        <StepIndicator />

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

        {/* Success message */}
        {status === 'success' && (
          <div
            className="mb-4 flex items-center gap-2 rounded-lg px-4 py-3 text-sm"
            style={{
              background: 'color-mix(in srgb, var(--accent-success) 10%, transparent)',
              border: '1px solid color-mix(in srgb, var(--accent-success) 25%, transparent)',
              color: 'var(--accent-success)',
            }}
            role="status"
          >
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
              <path d="M22 11.08V12a10 10 0 11-5.93-9.14" />
              <polyline points="22 4 12 14.01 9 11.01" />
            </svg>
            Password reset successful! Redirecting to login...
          </div>
        )}

        {/* ── Step 1: Email ── */}
        {step === 'email' && (
          <form onSubmit={handleSendEmail} className="space-y-4" noValidate>
            <AuthInput
              label="Email Address"
              type="email"
              placeholder="you@example.com"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              error={errors.email}
              autoComplete="email"
              disabled={status === 'loading'}
            />

            <button
              type="submit"
              disabled={status === 'loading'}
              className="flex h-11 w-full items-center justify-center rounded-lg text-sm font-semibold transition-all duration-200 disabled:cursor-not-allowed disabled:opacity-60"
              style={{
                background: 'linear-gradient(135deg, var(--color-aqua) 0%, var(--color-aqua-dark) 100%)',
                color: 'var(--color-white)',
                boxShadow: '0 4px 16px color-mix(in srgb, var(--color-aqua) 30%, transparent)',
              }}
            >
              {status === 'loading' ? (
                <svg className="h-5 w-5 animate-spin" viewBox="0 0 24 24" fill="none">
                  <circle cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="3" strokeDasharray="60" strokeLinecap="round" />
                </svg>
              ) : (
                'Send Reset Code'
              )}
            </button>

            <div className="text-center">
              <button
                type="button"
                onClick={() => router.visit('/login')}
                className="text-xs font-medium transition-colors hover:underline"
                style={{ color: 'var(--color-aqua)' }}
              >
                ← Back to Sign In
              </button>
            </div>
          </form>
        )}

        {/* ── Step 2: OTP Verify ── */}
        {step === 'otp' && (
          <form onSubmit={handleVerifyOtp} className="space-y-6" noValidate>
            <div className="text-center">
              <div
                className="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full"
                style={{ background: 'color-mix(in srgb, var(--color-aqua) 15%, transparent)' }}
              >
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--color-aqua)" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                  <rect x="2" y="4" width="20" height="16" rx="2" />
                  <path d="M22 7l-8.97 5.7a1.94 1.94 0 01-2.06 0L2 7" />
                </svg>
              </div>
              <p className="text-sm" style={{ color: 'var(--text-secondary)' }}>
                Code sent to
              </p>
              <p className="mt-1 text-sm font-semibold" style={{ color: 'var(--color-aqua)' }}>
                {email}
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
              className="flex h-11 w-full items-center justify-center rounded-lg text-sm font-semibold transition-all duration-200 disabled:cursor-not-allowed disabled:opacity-60"
              style={{
                background: 'linear-gradient(135deg, var(--color-aqua) 0%, var(--color-aqua-dark) 100%)',
                color: 'var(--color-white)',
                boxShadow: '0 4px 16px rgba(0, 181, 226, 0.3)',
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
                  setStep('email');
                  setOtpCode('');
                  setErrors({});
                  setServerError('');
                }}
                className="font-medium transition-colors hover:underline"
                style={{ color: 'var(--text-muted)' }}
              >
                ← Change email
              </button>

              {resendTimer > 0 ? (
                <span style={{ color: 'var(--text-disabled)' }}>
                  Resend in {resendTimer}s
                </span>
              ) : (
                <button
                  type="button"
                  onClick={handleResend}
                  disabled={status === 'loading'}
                  className="font-medium transition-colors hover:underline"
                  style={{ color: 'var(--color-aqua)' }}
                >
                  Resend code
                </button>
              )}
            </div>
          </form>
        )}

        {/* ── Step 3: New Password ── */}
        {step === 'reset' && status !== 'success' && (
          <form onSubmit={handleResetPassword} className="space-y-4" noValidate>
            <div>
              <AuthInput
                label="New Password"
                type={showPassword ? 'text' : 'password'}
                placeholder="••••••••"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                error={errors.password}
                autoComplete="new-password"
                disabled={status === 'loading'}
                rightElement={
                  <EyeToggle
                    show={showPassword}
                    onToggle={() => setShowPassword(!showPassword)}
                  />
                }
              />
              <PasswordStrengthBar password={password} />
            </div>

            <AuthInput
              label="Confirm Password"
              type={showConfirm ? 'text' : 'password'}
              placeholder="••••••••"
              value={passwordConfirm}
              onChange={(e) => setPasswordConfirm(e.target.value)}
              error={errors.password_confirmation}
              autoComplete="new-password"
              disabled={status === 'loading'}
              rightElement={
                <EyeToggle
                  show={showConfirm}
                  onToggle={() => setShowConfirm(!showConfirm)}
                />
              }
            />

            <button
              type="submit"
              disabled={status === 'loading'}
              className="flex h-11 w-full items-center justify-center rounded-lg text-sm font-semibold transition-all duration-200 disabled:cursor-not-allowed disabled:opacity-60"
              style={{
                background: 'linear-gradient(135deg, var(--color-aqua) 0%, var(--color-aqua-dark) 100%)',
                color: 'var(--color-white)',
                boxShadow: '0 4px 16px color-mix(in srgb, var(--color-aqua) 30%, transparent)',
              }}
            >
              {status === 'loading' ? (
                <svg className="h-5 w-5 animate-spin" viewBox="0 0 24 24" fill="none">
                  <circle cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="3" strokeDasharray="60" strokeLinecap="round" />
                </svg>
              ) : (
                'Reset Password'
              )}
            </button>
          </form>
        )}
      </AuthLayout>
    </>
  );
}

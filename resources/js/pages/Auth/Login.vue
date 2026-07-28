<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import CursorOrb from '@/modules/app/components/CursorOrb.vue';
import { computed, onMounted, ref, useTemplateRef, watch } from 'vue';
import { useCompany } from '@/modules/app/composables/useCompany';
import { apiFetch } from '@/lib/http';
import { loginFormSchema, emailOnlySchema } from '@/modules/auth/schemas/loginFormSchema';
import { resetPasswordFormSchema } from '@/modules/auth/schemas/resetPasswordFormSchema';
import type { SharedProps } from '@/types/inertia';

/**
 * Login — minimal-login-hero template (ported from the GUIDE Angular component)
 * wired to the Modules\Auth backend:
 *   · Password        → POST /login (Fortify)
 *   · Email code (OTP)→ POST /auth/otp/request + /auth/otp/login (spatie OTP)
 *   · Authenticator   → POST /two-factor-challenge (Fortify 2FA second factor)
 *   · Forgot/Reset    → POST /forgot-password + /reset-password (OTP reset)
 *   · Google          → GET /auth/{provider}/redirect (Socialite)
 */

type AuthMethod = 'password' | 'email_otp' | 'totp';

interface Social {
  key: 'facebook' | 'instagram' | 'linkedin' | 'github' | 'tiktok';
  label: string;
  url: string;
}

const props = withDefaults(
  defineProps<{
    canResetPassword?: boolean;
    status?: string | null;
    sessionExpired?: boolean;
  }>(),
  {
    canResetPassword: true,
    status: null,
    sessionExpired: false,
  },
);

const page = usePage<SharedProps>();
const company = useCompany();

// Guest hero <title> = DB brand name + env tagline (config.app_title_description).
const documentTitle = computed<string>(
    () => `${company.value.name} — ${page.props.config.app_title_description}`,
);

// ── Tab state ──
const authMethod = ref<AuthMethod>('password');

// ── Common state ──
const isLoading = ref(false);
const errorMessage = ref('');
const successMessage = ref('');
const sessionExpired = ref(props.sessionExpired);

// ── Password tab ──
const email = ref('');
const password = ref('');
const rememberMe = ref(false);
const showPassword = ref(false);

// ── Email OTP tab ──
const emailOtpEmail = ref('');
const emailOtpSent = ref(false);
const emailOtpDigits = ref<string[]>(['', '', '', '', '', '']);

// ── TOTP tab ──
const totpDigits = ref<string[]>(['', '', '', '', '', '']);
const trustDevice = ref(false);

// ── Forgot password ──
const forgotPasswordMode = ref(false);
const forgotEmail = ref('');
const forgotCodeSent = ref(false);
const resetCode = ref('');
const newPassword = ref('');
const confirmPassword = ref('');
const showNewPassword = ref(false);
const showConfirmPassword = ref(false);
const resetSuccess = ref(false);

// ── Template refs for OTP boxes ──
const otpInputs = useTemplateRef<HTMLInputElement[]>('otpInput');
const totpInputs = useTemplateRef<HTMLInputElement[]>('totpInput');
const starsHost = useTemplateRef<HTMLDivElement>('stars');

const currentYear = computed(() => new Date().getFullYear());

// GitHub / TikTok have no configured URL yet — kept empty so no dead link renders.
const socials = computed<Social[]>(() =>
  (
    [
      { key: 'facebook', label: 'Facebook', url: '' },
      { key: 'instagram', label: 'Instagram', url: '' },
      { key: 'linkedin', label: 'LinkedIn', url: '' },
      { key: 'github', label: 'GitHub', url: '' },
      { key: 'tiktok', label: 'TikTok', url: '' },
    ] satisfies Social[]
  ).filter((s) => s.url.length > 0),
);

// ── Helpers ──
const emailError = computed(() => {
  if (!email.value) {
    return '';
  }
  const parsed = emailOnlySchema.safeParse({ email: email.value });
  return parsed.success ? '' : (parsed.error.issues[0]?.message ?? 'Please enter a valid email address');
});
const passwordError = computed(() => {
  if (!password.value) {
    return '';
  }
  const parsed = loginFormSchema.safeParse({
    email: email.value || 'placeholder@example.com',
    password: password.value,
  });
  const issue = parsed.success
    ? undefined
    : parsed.error.issues.find((i) => i.path[0] === 'password');
  return issue?.message ?? '';
});

const isFormValid = computed(() =>
  loginFormSchema.safeParse({
    email: email.value,
    password: password.value,
    remember: rememberMe.value,
  }).success,
);
const canSubmitPassword = computed(() => isFormValid.value && !isLoading.value);

function isValidEmail(value: string): boolean {
  return emailOnlySchema.safeParse({ email: value }).success;
}

const emailOtpCode = computed(() => emailOtpDigits.value.join(''));
const canVerifyEmailOtp = computed(() => emailOtpCode.value.length === 6 && !isLoading.value);

const totpCode = computed(() => totpDigits.value.join(''));
const canVerifyTotp = computed(() => totpCode.value.length === 6 && !isLoading.value);

const canSubmitReset = computed(() =>
  resetPasswordFormSchema.safeParse({
    email: forgotEmail.value,
    code: resetCode.value,
    password: newPassword.value,
    password_confirmation: confirmPassword.value,
  }).success && !isLoading.value,
);

const newPasswordError = computed(() => {
  if (!newPassword.value) {
    return '';
  }
  const parsed = resetPasswordFormSchema.safeParse({
    email: forgotEmail.value || 'placeholder@example.com',
    code: resetCode.value || '1234',
    password: newPassword.value,
    password_confirmation: confirmPassword.value || newPassword.value,
  });
  const issue = parsed.success
    ? undefined
    : parsed.error.issues.find((i) => i.path[0] === 'password');
  return issue?.message ?? '';
});

const confirmPasswordError = computed(() => {
  if (!confirmPassword.value) {
    return '';
  }
  return confirmPassword.value !== newPassword.value ? 'Passwords do not match' : '';
});

// Clear the server error as soon as the user edits any field.
watch([email, password, emailOtpDigits, totpDigits], () => {
  if (errorMessage.value) {
    errorMessage.value = '';
  }
});

function setAuthMethod(method: AuthMethod): void {
  authMethod.value = method;
  errorMessage.value = '';
  successMessage.value = '';
  isLoading.value = false;
}

/** Pull the first Inertia validation message into the shake-alert banner. */
function surfaceErrors(errors: Record<string, string>): void {
  const first = Object.values(errors)[0];
  errorMessage.value = first ?? 'Something went wrong. Please try again.';
}

// ── Password ──
function onPasswordSubmit(): void {
  if (!canSubmitPassword.value) {
    return;
  }
  isLoading.value = true;
  errorMessage.value = '';
  successMessage.value = '';
  router.post(
    '/login',
    { email: email.value, password: password.value, remember: rememberMe.value },
    {
      onError: (errors) => surfaceErrors(errors),
      onFinish: () => (isLoading.value = false),
    },
  );
}

// ── Email OTP ──
function onSendEmailOtp(): void {
  if (!isValidEmail(emailOtpEmail.value)) {
    errorMessage.value = 'Please enter a valid email';
    return;
  }
  isLoading.value = true;
  errorMessage.value = '';
  router.post(
    '/auth/otp/request',
    { email: emailOtpEmail.value },
    {
      preserveState: true,
      preserveScroll: true,
      onSuccess: () => {
        emailOtpSent.value = true;
        successMessage.value = 'Login code sent! Check your email.';
        setTimeout(() => otpInputs.value?.[0]?.focus(), 100);
      },
      onError: (errors) => surfaceErrors(errors),
      onFinish: () => (isLoading.value = false),
    },
  );
}

function onVerifyEmailOtp(): void {
  if (!canVerifyEmailOtp.value) {
    return;
  }
  isLoading.value = true;
  errorMessage.value = '';
  router.post(
    '/auth/otp/login',
    { email: emailOtpEmail.value, one_time_password: emailOtpCode.value },
    {
      onError: (errors) => {
        surfaceErrors(errors);
        emailOtpDigits.value = ['', '', '', '', '', ''];
        setTimeout(() => otpInputs.value?.[0]?.focus(), 50);
      },
      onFinish: () => (isLoading.value = false),
    },
  );
}

// ── TOTP (Fortify second factor: needs an in-progress challenge session) ──
function onVerifyTotp(): void {
  if (!canVerifyTotp.value) {
    return;
  }
  isLoading.value = true;
  errorMessage.value = '';
  const shouldTrust = trustDevice.value;
  router.post(
    '/two-factor-challenge',
    { code: totpCode.value },
    {
      onSuccess: () => {
        // Now authenticated — persist the 30-day trusted-device cookie via a JSON
        // XHR so the redirect to the app isn't interrupted. Best-effort.
        if (shouldTrust) {
          apiFetch('POST', '/two-factor/trust-device').catch(() => {});
        }
      },
      onError: (errors) => {
        surfaceErrors(errors);
        totpDigits.value = ['', '', '', '', '', ''];
        setTimeout(() => totpInputs.value?.[0]?.focus(), 50);
      },
      onFinish: () => (isLoading.value = false),
    },
  );
}

// ── Forgot / reset ──
function onForgotPasswordSubmit(): void {
  if (!isValidEmail(forgotEmail.value)) {
    errorMessage.value = 'Please enter a valid email address';
    return;
  }
  isLoading.value = true;
  errorMessage.value = '';
  router.post(
    '/forgot-password',
    { email: forgotEmail.value },
    {
      preserveState: true,
      preserveScroll: true,
      onSuccess: () => {
        forgotCodeSent.value = true;
        successMessage.value = 'Reset code sent! Check your email.';
      },
      onError: (errors) => surfaceErrors(errors),
      onFinish: () => (isLoading.value = false),
    },
  );
}

function onResetPasswordSubmit(): void {
  if (!canSubmitReset.value) {
    return;
  }
  isLoading.value = true;
  errorMessage.value = '';
  router.post(
    '/reset-password',
    {
      email: forgotEmail.value,
      one_time_password: resetCode.value,
      password: newPassword.value,
      password_confirmation: confirmPassword.value,
    },
    {
      onSuccess: () => {
        resetSuccess.value = true;
        successMessage.value = 'Password reset successful! You can now sign in.';
      },
      onError: (errors) => surfaceErrors(errors),
      onFinish: () => (isLoading.value = false),
    },
  );
}

// ── Google ──
function onGoogleSignIn(): void {
  window.location.href = '/auth/google/redirect';
}

function backToSignIn(): void {
  forgotPasswordMode.value = false;
  resetSuccess.value = false;
  forgotCodeSent.value = false;
  resetCode.value = '';
  newPassword.value = '';
  confirmPassword.value = '';
  errorMessage.value = '';
  successMessage.value = '';
}

// ── OTP digit input handlers (shared by both 6-box grids) ──
type OtpKind = 'email' | 'totp';

function otpModel(kind: OtpKind) {
  return kind === 'email' ? emailOtpDigits : totpDigits;
}

function otpRefs(kind: OtpKind): HTMLInputElement[] | null {
  return kind === 'email' ? otpInputs.value : totpInputs.value;
}

function updateDigit(kind: OtpKind, index: number, event: Event): void {
  const model = otpModel(kind);
  const value = (event.target as HTMLInputElement).value.replace(/\D/g, '').slice(0, 1);
  const digits = [...model.value];
  digits[index] = value;
  model.value = digits;
  if (value && index < 5) {
    otpRefs(kind)?.[index + 1]?.focus();
  }
}

function handleDigitKeydown(kind: OtpKind, index: number, event: KeyboardEvent): void {
  const model = otpModel(kind);
  const refs = otpRefs(kind);
  const digits = [...model.value];
  if (event.key === 'Backspace') {
    if (!digits[index] && index > 0) {
      event.preventDefault();
      refs?.[index - 1]?.focus();
    } else if (digits[index]) {
      digits[index] = '';
      model.value = digits;
    }
  }
  if (event.key === 'ArrowLeft' && index > 0) {
    event.preventDefault();
    refs?.[index - 1]?.focus();
  }
  if (event.key === 'ArrowRight' && index < 5) {
    event.preventDefault();
    refs?.[index + 1]?.focus();
  }
}

function handleDigitPaste(kind: OtpKind, event: ClipboardEvent): void {
  const model = otpModel(kind);
  event.preventDefault();
  const paste = event.clipboardData?.getData('text') ?? '';
  const digits = paste.replace(/\D/g, '').slice(0, 6).split('');
  const current = ['', '', '', '', '', ''];
  digits.forEach((d, i) => {
    if (i < 6) {
      current[i] = d;
    }
  });
  model.value = current;
  otpRefs(kind)?.[Math.min(digits.length, 5)]?.focus();
}

// ── Animated particles ──
onMounted(() => {
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    return;
  }
  const host = starsHost.value;
  if (!host) {
    return;
  }
  for (let i = 0; i < 45; i++) {
    const star = document.createElement('div');
    star.className = 'star';
    const big = Math.random() > 0.7;
    star.style.cssText = `left:${Math.random() * 100}%;top:${Math.random() * 100}%;--d:${2 + Math.random() * 5}s;--delay:${Math.random() * 6}s;--op:${0.2 + Math.random() * 0.6};width:${big ? 3 : 2}px;height:${big ? 3 : 2}px;`;
    host.appendChild(star);
  }
});

// Surface a server-provided flash status (e.g. after a redirect back to /login).
watch(
  () => page.props.status as string | undefined,
  (value) => {
    if (value === 'password-reset') {
      successMessage.value = 'Password reset successful! You can now sign in.';
    } else if (value) {
      successMessage.value = value;
    }
  },
  { immediate: true },
);
</script>

<template>
  <Head :title="documentTitle" />

  <CursorOrb />

  <div class="auth-hero">
    <div class="hero-bg" aria-hidden="true"></div>
    <div ref="stars" class="hero-stars" aria-hidden="true"></div>

    <div class="hero-wrap">
      <!-- NAV -->
      <nav class="hero-nav">
        <div class="brand">
          <img :src="company.logo_white_url" :alt="company.name" class="brand-logo" />
        </div>
      </nav>

      <!-- HERO GRID -->
      <div class="hero-grid">
        <!-- LEFT: copy -->
        <div class="copy">
          <div class="eyebrow">Your AI workspace</div>
          <h1 class="hero-title">
            AI-powered workspace<br />for <span class="grad">creators</span><br />and
            <span class="grad">educators</span>
          </h1>
          <p class="hero-sub">
            Create, collaborate and scale your ideas with the power of AI — scripts and PDFs, video
            editing, attendance, invoicing and marketing in one place.
          </p>
          <ul class="feature-list">
            <li>
              <span class="chk"
                ><svg fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                  <polyline points="20 6 9 17 4 12" /></svg
              ></span>
              Scripts + practical material as PDF
            </li>
            <li>
              <span class="chk"
                ><svg fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                  <polyline points="20 6 9 17 4 12" /></svg
              ></span>
              Video editor: removes silences and bad cuts
            </li>
            <li>
              <span class="chk"
                ><svg fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                  <polyline points="20 6 9 17 4 12" /></svg
              ></span>
              Attendance, invoicing and AI marketing
            </li>
            <li>
              <span class="chk"
                ><svg fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                  <polyline points="20 6 9 17 4 12" /></svg
              ></span>
              Schedule and publish social media campaigns
            </li>
          </ul>

          <div class="actions">
            <button type="button" class="cta-ghost" aria-label="Watch video">
              <span class="play">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor">
                  <path d="M7 5v14l12-7z" />
                </svg>
              </span>
              Watch Video
            </button>
          </div>

          <div class="quick">
            <div class="qcard">
              <div class="ic">
                <svg
                  width="22"
                  height="22"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="1.8"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                >
                  <path d="M9 18h6M10 21h4M12 3a6 6 0 0 0-4 10.5c.5.5 1 1.2 1 2v.5h6v-.5c0-.8.5-1.5 1-2A6 6 0 0 0 12 3z" />
                </svg>
              </div>
              <span>Ideas</span>
            </div>
            <div class="qcard">
              <div class="ic">
                <svg
                  width="22"
                  height="22"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="1.8"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                >
                  <rect x="4" y="3" width="16" height="18" rx="2" />
                  <path d="M8 8h6M8 12h8M8 16h5" />
                </svg>
              </div>
              <span>Content</span>
            </div>
            <div class="qcard">
              <div class="ic">
                <svg
                  width="22"
                  height="22"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="1.8"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                >
                  <path d="M4 5a2 2 0 0 1 2-2h13v15H6a2 2 0 0 0-2 2z" />
                  <path d="M4 19a2 2 0 0 0 2 2h13" />
                </svg>
              </div>
              <span>Courses</span>
            </div>
            <div class="qcard">
              <div class="ic">
                <svg
                  width="22"
                  height="22"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="1.8"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                >
                  <path d="M4 20V10M10 20V4M16 20v-7M22 20H2" />
                </svg>
              </div>
              <span>Analytics</span>
            </div>
            <div class="qcard">
              <div class="ic">
                <svg
                  width="22"
                  height="22"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="1.8"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                >
                  <circle cx="18" cy="5" r="3" />
                  <circle cx="6" cy="12" r="3" />
                  <circle cx="18" cy="19" r="3" />
                  <line x1="8.59" y1="13.51" x2="15.42" y2="17.49" />
                  <line x1="15.41" y1="6.51" x2="8.59" y2="10.49" />
                </svg>
              </div>
              <span>Social</span>
            </div>
          </div>
        </div>

        <!-- RIGHT: login card -->
        <div class="hero-right">
          <section class="auth-card">
            <div class="login-header">
              <img class="card-mark" :src="company.mark_url" alt="" aria-hidden="true" />
              <div class="card-eyebrow">Access · {{ company.name }}</div>
              <h1 class="login-title">Welcome Back</h1>
              <p class="login-subtitle">Sign in to pick up where you left off.</p>
            </div>

            <nav class="auth-tabs">
              <button
                type="button"
                class="auth-tab"
                :class="{ active: authMethod === 'password' }"
                @click="setAuthMethod('password')"
              >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <rect x="3" y="11" width="18" height="11" rx="2" ry="2" />
                  <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                </svg>
                Password
              </button>
              <button
                type="button"
                class="auth-tab"
                :class="{ active: authMethod === 'email_otp' }"
                @click="setAuthMethod('email_otp')"
              >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" />
                  <polyline points="22,6 12,13 2,6" />
                </svg>
                Email code
              </button>
              <button
                type="button"
                class="auth-tab"
                :class="{ active: authMethod === 'totp' }"
                @click="setAuthMethod('totp')"
              >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <circle cx="12" cy="12" r="10" />
                  <polyline points="12 6 12 12 16 14" />
                </svg>
                Authenticator
              </button>
            </nav>

            <div class="tab-content">
              <template v-if="!forgotPasswordMode">
                <div v-if="sessionExpired" class="session-expired-banner">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10" />
                    <path d="M12 8v4" />
                    <path d="M12 16h.01" />
                  </svg>
                  <span>Your session has expired. Please sign in again.</span>
                </div>
                <div v-if="successMessage" class="success-banner">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
                    <polyline points="22 4 12 14.01 9 11.01" />
                  </svg>
                  <span>{{ successMessage }}</span>
                </div>
                <div v-if="errorMessage" class="alert alert-error">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10" />
                    <line x1="12" y1="8" x2="12" y2="12" />
                    <line x1="12" y1="16" x2="12.01" y2="16" />
                  </svg>
                  <span>{{ errorMessage }}</span>
                </div>

                <!-- PASSWORD -->
                <template v-if="authMethod === 'password'">
                  <form class="login-form" @submit.prevent="onPasswordSubmit">
                    <div class="form-group">
                      <div class="input-wrapper">
                        <input
                          id="email"
                          v-model="email"
                          type="email"
                          placeholder=" "
                          class="form-input"
                          :class="{ error: emailError }"
                          autocomplete="email"
                        />
                        <label for="email" class="form-label">Email Address</label>
                        <div class="input-icon">
                          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" />
                            <polyline points="22,6 12,13 2,6" />
                          </svg>
                        </div>
                      </div>
                      <span v-if="emailError" class="error-message">{{ emailError }}</span>
                    </div>

                    <div class="form-group">
                      <div class="input-wrapper">
                        <input
                          id="password"
                          v-model="password"
                          :type="showPassword ? 'text' : 'password'"
                          placeholder=" "
                          class="form-input"
                          :class="{ error: passwordError }"
                          autocomplete="current-password"
                        />
                        <label for="password" class="form-label">Password</label>
                        <button
                          type="button"
                          class="toggle-password"
                          aria-label="Toggle password"
                          @click="showPassword = !showPassword"
                        >
                          <svg
                            v-if="showPassword"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                          >
                            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24" />
                            <line x1="1" y1="1" x2="23" y2="23" />
                          </svg>
                          <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                            <circle cx="12" cy="12" r="3" />
                          </svg>
                        </button>
                      </div>
                      <span v-if="passwordError" class="error-message">{{ passwordError }}</span>
                    </div>

                    <div class="form-row-between">
                      <label
                        class="remember-me"
                        :class="{ checked: rememberMe }"
                        @click="rememberMe = !rememberMe"
                      >
                        <span class="checkbox-visual">
                          <svg
                            v-if="rememberMe"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="3"
                          >
                            <polyline points="20 6 9 17 4 12" />
                          </svg>
                        </span>
                        <span class="checkbox-label">Remember me</span>
                      </label>
                      <button
                        v-if="canResetPassword"
                        type="button"
                        class="link-text"
                        @click="forgotPasswordMode = true; errorMessage = ''; successMessage = ''"
                      >
                        Forgot password?
                      </button>
                    </div>

                    <button type="submit" class="submit-button" :disabled="!canSubmitPassword">
                      <svg v-if="isLoading" class="spinner" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 12a9 9 0 1 1-6.219-8.56" />
                      </svg>
                      <span>{{ isLoading ? 'Signing in…' : 'Sign In' }}</span>
                    </button>
                  </form>

                  <div class="divider">
                    <span class="divider-line"></span>
                    <span class="divider-text">or continue with</span>
                    <span class="divider-line"></span>
                  </div>

                  <button type="button" class="google-btn" @click="onGoogleSignIn">
                    <svg class="google-icon" viewBox="0 0 24 24">
                      <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4" />
                      <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853" />
                      <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05" />
                      <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335" />
                    </svg>
                    <span>Continue with Google</span>
                  </button>
                </template>

                <!-- EMAIL OTP -->
                <template v-else-if="authMethod === 'email_otp'">
                  <div v-if="!emailOtpSent" class="login-form">
                    <div class="form-group">
                      <div class="input-wrapper">
                        <input
                          id="emailOtp"
                          v-model="emailOtpEmail"
                          type="email"
                          placeholder=" "
                          class="form-input"
                          autocomplete="email"
                        />
                        <label for="emailOtp" class="form-label">Email Address</label>
                        <div class="input-icon">
                          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" />
                            <polyline points="22,6 12,13 2,6" />
                          </svg>
                        </div>
                      </div>
                    </div>
                    <button
                      type="button"
                      class="submit-button"
                      :disabled="!isValidEmail(emailOtpEmail) || isLoading"
                      @click="onSendEmailOtp"
                    >
                      <span>{{ isLoading ? 'Sending…' : 'Send login code' }}</span>
                    </button>
                    <p class="tab-hint">We will send a one-time code to your email.</p>
                  </div>
                  <div v-else class="login-form">
                    <p class="otp-hint">
                      Enter the 6-digit code sent to <strong>{{ emailOtpEmail }}</strong>
                    </p>
                    <div class="otp-inputs">
                      <input
                        v-for="(digit, i) in emailOtpDigits"
                        :key="i"
                        ref="otpInput"
                        type="text"
                        inputmode="numeric"
                        maxlength="1"
                        class="otp-digit"
                        :class="{ filled: digit }"
                        :value="digit"
                        @input="updateDigit('email', i, $event)"
                        @keydown="handleDigitKeydown('email', i, $event)"
                        @paste="handleDigitPaste('email', $event)"
                      />
                    </div>
                    <button
                      type="button"
                      class="submit-button"
                      :disabled="!canVerifyEmailOtp"
                      @click="onVerifyEmailOtp"
                    >
                      <span>{{ isLoading ? 'Verifying…' : 'Verify & Sign In' }}</span>
                    </button>
                    <button
                      type="button"
                      class="back-link"
                      @click="emailOtpSent = false; emailOtpDigits = ['', '', '', '', '', '']"
                    >
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="15 18 9 12 15 6" />
                      </svg>
                      Use a different email
                    </button>
                  </div>
                </template>

                <!-- TOTP -->
                <template v-else>
                  <div class="login-form">
                    <p class="otp-hint">Enter the 6-digit code from your authenticator app</p>
                    <div class="otp-inputs">
                      <input
                        v-for="(digit, i) in totpDigits"
                        :key="i"
                        ref="totpInput"
                        type="text"
                        inputmode="numeric"
                        maxlength="1"
                        class="otp-digit"
                        :class="{ filled: digit }"
                        :value="digit"
                        @input="updateDigit('totp', i, $event)"
                        @keydown="handleDigitKeydown('totp', i, $event)"
                        @paste="handleDigitPaste('totp', $event)"
                      />
                    </div>
                    <label class="trust-device-option">
                      <input v-model="trustDevice" type="checkbox" class="crud-checkbox" />
                      <span>Trust this device for 30 days</span>
                    </label>
                    <button
                      type="button"
                      class="submit-button"
                      :disabled="!canVerifyTotp"
                      @click="onVerifyTotp"
                    >
                      <span>{{ isLoading ? 'Verifying…' : 'Verify & Sign In' }}</span>
                    </button>
                  </div>
                </template>
              </template>

              <!-- FORGOT / RESET -->
              <template v-else>
                <div v-if="resetSuccess" class="login-form">
                  <div class="success-state" style="padding: var(--space-4) 0">
                    <div class="success-checkmark" style="width: var(--space-16); height: var(--space-16)">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
                        <polyline points="22 4 12 14.01 9 11.01" />
                      </svg>
                    </div>
                    <p class="success-text">{{ successMessage }}</p>
                  </div>
                  <button type="button" class="submit-button" @click="backToSignIn">Sign In</button>
                </div>

                <template v-else>
                  <div v-if="errorMessage" class="alert alert-error">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <circle cx="12" cy="12" r="10" />
                      <line x1="12" y1="8" x2="12" y2="12" />
                      <line x1="12" y1="16" x2="12.01" y2="16" />
                    </svg>
                    <span>{{ errorMessage }}</span>
                  </div>

                  <form
                    v-if="!forgotCodeSent"
                    class="login-form"
                    @submit.prevent="onForgotPasswordSubmit"
                  >
                    <p class="otp-hint">Enter your email and we will send you a reset code.</p>
                    <div class="form-group">
                      <div class="input-wrapper">
                        <input
                          id="forgotEmail"
                          v-model="forgotEmail"
                          type="email"
                          placeholder=" "
                          class="form-input"
                          autocomplete="email"
                        />
                        <label for="forgotEmail" class="form-label">Email Address</label>
                        <div class="input-icon">
                          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" />
                            <polyline points="22,6 12,13 2,6" />
                          </svg>
                        </div>
                      </div>
                    </div>
                    <button
                      type="submit"
                      class="submit-button"
                      :disabled="!isValidEmail(forgotEmail) || isLoading"
                    >
                      <span>{{ isLoading ? 'Sending…' : 'Send Reset Code' }}</span>
                    </button>
                    <button type="button" class="back-link" @click="backToSignIn">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="15 18 9 12 15 6" />
                      </svg>
                      Back to sign in
                    </button>
                  </form>

                  <form v-else class="login-form" @submit.prevent="onResetPasswordSubmit">
                    <p class="otp-hint">
                      Enter the reset code sent to <strong>{{ forgotEmail }}</strong> and your new
                      password.
                    </p>

                    <div class="form-group">
                      <div class="input-wrapper">
                        <input
                          id="resetCode"
                          v-model="resetCode"
                          type="text"
                          placeholder=" "
                          class="form-input"
                          autocomplete="one-time-code"
                        />
                        <label for="resetCode" class="form-label">Reset Code</label>
                      </div>
                    </div>

                    <div class="form-group">
                      <div class="input-wrapper">
                        <input
                          id="newPassword"
                          v-model="newPassword"
                          :type="showNewPassword ? 'text' : 'password'"
                          placeholder=" "
                          class="form-input"
                          autocomplete="new-password"
                        />
                        <label for="newPassword" class="form-label">New Password</label>
                        <button
                          type="button"
                          class="toggle-password"
                          aria-label="Toggle new password"
                          @click="showNewPassword = !showNewPassword"
                        >
                          <svg v-if="showNewPassword" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24" />
                            <line x1="1" y1="1" x2="23" y2="23" />
                          </svg>
                          <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                            <circle cx="12" cy="12" r="3" />
                          </svg>
                        </button>
                      </div>
                      <span v-if="newPasswordError" class="error-message">
                        {{ newPasswordError }}
                      </span>
                    </div>

                    <div class="form-group">
                      <div class="input-wrapper">
                        <input
                          id="confirmPassword"
                          v-model="confirmPassword"
                          :type="showConfirmPassword ? 'text' : 'password'"
                          placeholder=" "
                          class="form-input"
                          autocomplete="new-password"
                        />
                        <label for="confirmPassword" class="form-label">Confirm Password</label>
                        <button
                          type="button"
                          class="toggle-password"
                          aria-label="Toggle confirm password"
                          @click="showConfirmPassword = !showConfirmPassword"
                        >
                          <svg v-if="showConfirmPassword" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24" />
                            <line x1="1" y1="1" x2="23" y2="23" />
                          </svg>
                          <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                            <circle cx="12" cy="12" r="3" />
                          </svg>
                        </button>
                      </div>
                      <span
                        v-if="confirmPasswordError"
                        class="error-message"
                      >
                        {{ confirmPasswordError }}
                      </span>
                    </div>

                    <button type="submit" class="submit-button" :disabled="!canSubmitReset">
                      <span>{{ isLoading ? 'Resetting…' : 'Reset Password' }}</span>
                    </button>

                    <button
                      type="button"
                      class="back-link"
                      @click="forgotCodeSent = false; resetCode = ''; newPassword = ''; confirmPassword = ''; errorMessage = ''; successMessage = ''"
                    >
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="15 18 9 12 15 6" />
                      </svg>
                      Use a different email
                    </button>
                  </form>
                </template>
              </template>
            </div>
          </section>
        </div>
      </div>

      <footer class="hero-foot">
        <div class="hero-foot-row">
          <span class="foot-copy"
            >© {{ currentYear }} {{ company.name }} · Private access · internal use only</span
          >

          <div v-if="socials.length" class="social-links">
            <a
              v-for="s in socials"
              :key="s.key"
              :class="'social-link social-' + s.key"
              :href="s.url"
              target="_blank"
              rel="noopener noreferrer"
              :aria-label="s.label"
              :title="s.label"
            >
              {{ s.label.charAt(0) }}
            </a>
          </div>
        </div>

        <a class="foot-credit" href="https://argenis.dev" target="_blank" rel="noopener noreferrer">
          Developed by <span>argenis.dev</span>
        </a>
      </footer>
    </div>
  </div>
</template>

<style src="./login.css"></style>

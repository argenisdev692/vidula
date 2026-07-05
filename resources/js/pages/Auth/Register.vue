<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, onMounted, ref, useTemplateRef } from 'vue';
import { useCompany } from '@/modules/app/composables/useCompany';

/**
 * Register — same dark-hero shell as Login, wired to Fortify registration:
 *   · POST /register  (first_name, last_name, email, password,
 *     password_confirmation, terms_and_conditions)
 * On success Fortify redirects to the app home; unverified users receive a
 * 6-digit activation code by email (see App\Actions\Fortify\CreateNewUser).
 */

const company = useCompany();

const firstName = ref('');
const lastName = ref('');
const email = ref('');
const password = ref('');
const passwordConfirmation = ref('');
const acceptedTerms = ref(false);

const showPassword = ref(false);
const showConfirm = ref(false);
const isLoading = ref(false);
const errorMessage = ref('');
const fieldErrors = ref<Record<string, string>>({});

const starsHost = useTemplateRef<HTMLDivElement>('stars');

function isValidEmail(value: string): boolean {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
}

const emailError = computed(() =>
  !email.value ? '' : !isValidEmail(email.value) ? 'Please enter a valid email address' : '',
);
const passwordError = computed(() =>
  !password.value ? '' : password.value.length < 8 ? 'Password must be at least 8 characters' : '',
);
const confirmError = computed(() =>
  !passwordConfirmation.value
    ? ''
    : passwordConfirmation.value !== password.value
      ? 'Passwords do not match'
      : '',
);

const canSubmit = computed(
  () =>
    firstName.value.trim().length > 0 &&
    isValidEmail(email.value) &&
    password.value.length >= 8 &&
    passwordConfirmation.value === password.value &&
    acceptedTerms.value &&
    !isLoading.value,
);

function onSubmit(): void {
  if (!canSubmit.value) {
    return;
  }
  isLoading.value = true;
  errorMessage.value = '';
  fieldErrors.value = {};
  router.post(
    '/register',
    {
      first_name: firstName.value,
      last_name: lastName.value,
      email: email.value,
      password: password.value,
      password_confirmation: passwordConfirmation.value,
      terms_and_conditions: acceptedTerms.value,
    },
    {
      onError: (errors) => {
        fieldErrors.value = errors;
        errorMessage.value = Object.values(errors)[0] ?? 'Something went wrong. Please try again.';
      },
      onFinish: () => (isLoading.value = false),
    },
  );
}

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
</script>

<template>
  <Head title="Create account" />

  <div class="auth-hero">
    <div class="hero-bg" aria-hidden="true"></div>
    <div ref="stars" class="hero-stars" aria-hidden="true"></div>

    <div class="hero-wrap">
      <nav class="hero-nav">
        <div class="brand">
          <img :src="company.logo_white_url" :alt="company.name" class="brand-logo" />
        </div>
      </nav>

      <div class="auth-solo">
        <section class="auth-card">
          <div class="login-header">
            <img class="card-mark" :src="company.mark_url" alt="" aria-hidden="true" />
            <div class="card-eyebrow">Register · {{ company.name }}</div>
            <h1 class="login-title">Create your account</h1>
            <p class="login-subtitle">Start building with the Vidula workspace.</p>
          </div>

          <div class="tab-content">
            <div v-if="errorMessage" class="alert alert-error">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10" />
                <line x1="12" y1="8" x2="12" y2="12" />
                <line x1="12" y1="16" x2="12.01" y2="16" />
              </svg>
              <span>{{ errorMessage }}</span>
            </div>

            <form class="login-form" @submit.prevent="onSubmit">
              <div class="form-group">
                <div class="input-wrapper">
                  <input
                    id="firstName"
                    v-model="firstName"
                    type="text"
                    placeholder=" "
                    class="form-input"
                    autocomplete="given-name"
                  />
                  <label for="firstName" class="form-label">First name</label>
                </div>
                <span v-if="fieldErrors.first_name" class="error-message">{{ fieldErrors.first_name }}</span>
              </div>

              <div class="form-group">
                <div class="input-wrapper">
                  <input
                    id="lastName"
                    v-model="lastName"
                    type="text"
                    placeholder=" "
                    class="form-input"
                    autocomplete="family-name"
                  />
                  <label for="lastName" class="form-label">Last name (optional)</label>
                </div>
                <span v-if="fieldErrors.last_name" class="error-message">{{ fieldErrors.last_name }}</span>
              </div>

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
                  <label for="email" class="form-label">Email address</label>
                  <div class="input-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" />
                      <polyline points="22,6 12,13 2,6" />
                    </svg>
                  </div>
                </div>
                <span v-if="emailError || fieldErrors.email" class="error-message">
                  {{ emailError || fieldErrors.email }}
                </span>
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
                    autocomplete="new-password"
                  />
                  <label for="password" class="form-label">Password</label>
                  <button
                    type="button"
                    class="toggle-password"
                    aria-label="Toggle password"
                    @click="showPassword = !showPassword"
                  >
                    <svg v-if="showPassword" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24" />
                      <line x1="1" y1="1" x2="23" y2="23" />
                    </svg>
                    <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                      <circle cx="12" cy="12" r="3" />
                    </svg>
                  </button>
                </div>
                <span v-if="passwordError || fieldErrors.password" class="error-message">
                  {{ passwordError || fieldErrors.password }}
                </span>
              </div>

              <div class="form-group">
                <div class="input-wrapper">
                  <input
                    id="passwordConfirmation"
                    v-model="passwordConfirmation"
                    :type="showConfirm ? 'text' : 'password'"
                    placeholder=" "
                    class="form-input"
                    :class="{ error: confirmError }"
                    autocomplete="new-password"
                  />
                  <label for="passwordConfirmation" class="form-label">Confirm password</label>
                  <button
                    type="button"
                    class="toggle-password"
                    aria-label="Toggle confirm password"
                    @click="showConfirm = !showConfirm"
                  >
                    <svg v-if="showConfirm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24" />
                      <line x1="1" y1="1" x2="23" y2="23" />
                    </svg>
                    <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                      <circle cx="12" cy="12" r="3" />
                    </svg>
                  </button>
                </div>
                <span v-if="confirmError" class="error-message">{{ confirmError }}</span>
              </div>

              <label
                class="remember-me"
                :class="{ checked: acceptedTerms }"
                @click="acceptedTerms = !acceptedTerms"
              >
                <span class="checkbox-visual">
                  <svg v-if="acceptedTerms" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                    <polyline points="20 6 9 17 4 12" />
                  </svg>
                </span>
                <span class="checkbox-label">I accept the terms and conditions</span>
              </label>
              <span v-if="fieldErrors.terms_and_conditions" class="error-message">
                {{ fieldErrors.terms_and_conditions }}
              </span>

              <button type="submit" class="submit-button" :disabled="!canSubmit">
                <svg v-if="isLoading" class="spinner" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M21 12a9 9 0 1 1-6.219-8.56" />
                </svg>
                <span>{{ isLoading ? 'Creating account…' : 'Create account' }}</span>
              </button>
            </form>

            <div class="divider">
              <span class="divider-line"></span>
              <span class="divider-text">already have an account?</span>
              <span class="divider-line"></span>
            </div>

            <Link href="/login" class="back-link">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="15 18 9 12 15 6" />
              </svg>
              Back to sign in
            </Link>
          </div>
        </section>
      </div>
    </div>
  </div>
</template>

<style src="./login.css"></style>

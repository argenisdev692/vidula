<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, onMounted, ref, useTemplateRef } from 'vue';
import { useCompany } from '@/modules/app/composables/useCompany';

/**
 * Forgot password — step 1 of the passwordless (OTP) reset:
 *   · POST /forgot-password  { email }  → emails a 6-digit code (30 min)
 * The backend responds `back()` with a generic status (no user enumeration),
 * so on success we forward to /reset-password with the email prefilled.
 */

withDefaults(
  defineProps<{
    status?: string | null;
  }>(),
  {
    status: null,
  },
);

const company = useCompany();

const email = ref('');
const isLoading = ref(false);
const errorMessage = ref('');

const starsHost = useTemplateRef<HTMLDivElement>('stars');

function isValidEmail(value: string): boolean {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
}

const canSubmit = computed(() => isValidEmail(email.value) && !isLoading.value);

function onSubmit(): void {
  if (!canSubmit.value) {
    return;
  }
  isLoading.value = true;
  errorMessage.value = '';
  router.post(
    '/forgot-password',
    { email: email.value },
    {
      preserveState: true,
      preserveScroll: true,
      onSuccess: () => {
        router.visit(`/reset-password?email=${encodeURIComponent(email.value)}`);
      },
      onError: (errors) => {
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
  <Head title="Forgot password" />

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
            <div class="card-eyebrow">Recover · {{ company.name }}</div>
            <h1 class="login-title">Forgot your password?</h1>
            <p class="login-subtitle">Enter your email and we will send you a reset code.</p>
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
                    id="email"
                    v-model="email"
                    type="email"
                    placeholder=" "
                    class="form-input"
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
              </div>

              <button type="submit" class="submit-button" :disabled="!canSubmit">
                <svg v-if="isLoading" class="spinner" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M21 12a9 9 0 1 1-6.219-8.56" />
                </svg>
                <span>{{ isLoading ? 'Sending…' : 'Send reset code' }}</span>
              </button>
              <p class="tab-hint">We will send a 6-digit code valid for 30 minutes.</p>
            </form>

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

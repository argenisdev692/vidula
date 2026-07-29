<script setup lang="ts">
/**
 * Password confirmation — Fortify GET /user/confirm-password.
 * Shown when password.confirm middleware blocks a sensitive action (2FA enable /
 * QR / confirm / disable) and the request was not JSON. On success Fortify
 * redirects to url.intended or fortify.home (/dashboard).
 */
import { Head, router } from '@inertiajs/vue3';
import CursorOrb from '@/modules/app/components/CursorOrb.vue';
import { computed, ref } from 'vue';
import { useCompany } from '@/modules/app/composables/useCompany';

const company = useCompany();

const documentTitle = computed<string>(() => `Confirm password — ${company.value.name}`);

const password = ref<string>('');
const isLoading = ref<boolean>(false);
const errorMessage = ref<string>('');

const canSubmit = computed<boolean>(() => password.value.length > 0 && !isLoading.value);

function submit(): void {
  if (!canSubmit.value) {
    return;
  }
  isLoading.value = true;
  errorMessage.value = '';
  router.post(
    '/user/confirm-password',
    { password: password.value },
    {
      onError: (errors) => {
        errorMessage.value = Object.values(errors)[0] ?? 'Incorrect password. Please try again.';
        password.value = '';
      },
      onFinish: () => {
        isLoading.value = false;
      },
    },
  );
}
</script>

<template>
  <Head :title="documentTitle" />

  <CursorOrb />

  <div class="cfp">
    <section class="cfp__card">
      <img class="cfp__mark" :src="company.mark_url" alt="" aria-hidden="true" />
      <h1 class="cfp__title">Confirm your password</h1>
      <p class="cfp__subtitle">For your security, please confirm your password to continue.</p>

      <div v-if="errorMessage" class="cfp__alert" role="alert">{{ errorMessage }}</div>

      <form class="cfp__form" @submit.prevent="submit">
        <input
          v-model="password"
          type="password"
          class="cfp__input"
          placeholder="Password"
          autocomplete="current-password"
          aria-label="Password"
          autofocus
        />
        <button type="submit" class="cfp__submit" :disabled="!canSubmit">
          {{ isLoading ? 'Confirming…' : 'Confirm' }}
        </button>
      </form>
    </section>
  </div>
</template>

<style scoped>
.cfp {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: var(--space-6);
  background: var(--bg-app);
  font-family: var(--font-sans);
}

.cfp__card {
  width: 100%;
  max-width: 26rem;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  gap: var(--space-3);
  padding: var(--space-8) var(--space-6);
  border: 1px solid var(--border-default);
  border-radius: var(--radius-2xl);
  background: color-mix(in srgb, var(--bg-surface) 70%, transparent);
  backdrop-filter: blur(16px);
}

.cfp__mark {
  width: var(--space-12);
  height: var(--space-12);
  object-fit: contain;
  margin-bottom: var(--space-2);
}

.cfp__title {
  margin: 0;
  font-size: var(--text-2xl);
  font-weight: var(--font-bold);
  color: var(--text-primary);
}

.cfp__subtitle {
  margin: 0;
  color: var(--text-secondary);
  font-size: var(--text-sm);
}

.cfp__alert {
  width: 100%;
  padding: var(--space-3) var(--space-4);
  border-radius: var(--radius-md);
  border: 1px solid color-mix(in srgb, var(--accent-error) 40%, transparent);
  background: color-mix(in srgb, var(--accent-error) 12%, transparent);
  color: var(--accent-error);
  font-size: var(--text-sm);
}

.cfp__form {
  width: 100%;
  display: flex;
  flex-direction: column;
  gap: var(--space-3);
  margin-top: var(--space-2);
}

.cfp__input {
  width: 100%;
  padding: var(--space-3) var(--space-4);
  border-radius: var(--radius-md);
  border: 1px solid var(--border-strong);
  background: var(--bg-elevated);
  color: var(--text-primary);
  font-size: var(--text-sm);
}

.cfp__input:focus {
  outline: none;
  border-color: var(--accent-primary);
}

.cfp__submit {
  width: 100%;
  padding: var(--space-3) var(--space-4);
  border-radius: var(--radius-md);
  border: 1px solid var(--accent-primary);
  background: var(--accent-primary);
  color: var(--text-on-accent);
  font-weight: var(--font-semibold);
  cursor: pointer;
  transition: opacity var(--transition-fast);
}

.cfp__submit:hover {
  opacity: 0.92;
}

.cfp__submit:disabled {
  opacity: 0.6;
  pointer-events: none;
}
</style>

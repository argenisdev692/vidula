<script setup lang="ts">
/**
 * Two-factor challenge — the second step Fortify redirects to after a correct
 * password when 2FA is enabled. Submits either a 6-digit authenticator code or a
 * one-time recovery code to POST /two-factor-challenge. Rendered bare (no app
 * shell), mirroring the Login page.
 */
import { Head, router } from '@inertiajs/vue3';
import CursorOrb from '@/modules/app/components/CursorOrb.vue';
import { computed, ref } from 'vue';
import { useCompany } from '@/modules/app/composables/useCompany';
import { apiFetch } from '@/lib/http';

const company = useCompany();

// Guest <title> = page name + DB company name.
const documentTitle = computed<string>(() => `Two-factor authentication — ${company.value.name}`);

const useRecovery = ref<boolean>(false);
const code = ref<string>('');
const recoveryCode = ref<string>('');
const trustDevice = ref<boolean>(false);
const isLoading = ref<boolean>(false);
const errorMessage = ref<string>('');

const canSubmit = computed<boolean>(() =>
  useRecovery.value ? recoveryCode.value.trim().length > 0 : code.value.length === 6,
);

function toggleMode(): void {
  useRecovery.value = !useRecovery.value;
  errorMessage.value = '';
  code.value = '';
  recoveryCode.value = '';
}

function submit(): void {
  if (!canSubmit.value || isLoading.value) {
    return;
  }
  isLoading.value = true;
  errorMessage.value = '';
  const payload = useRecovery.value
    ? { recovery_code: recoveryCode.value.trim() }
    : { code: code.value };

  const shouldTrust = trustDevice.value;

  router.post('/two-factor-challenge', payload, {
    onSuccess: () => {
      // Now authenticated — persist the 30-day trusted-device cookie via a JSON
      // XHR so we don't bounce the user off the page they were redirected to.
      if (shouldTrust) {
        apiFetch('POST', '/two-factor/trust-device').catch(() => {
          /* Trusting is best-effort; sign-in already succeeded. */
        });
      }
    },
    onError: (errors) => {
      errorMessage.value = Object.values(errors)[0] ?? 'That code is invalid. Please try again.';
      code.value = '';
    },
    onFinish: () => {
      isLoading.value = false;
    },
  });
}
</script>

<template>
  <Head :title="documentTitle" />

  <CursorOrb />

  <div class="tfc">
    <section class="tfc__card">
      <img class="tfc__mark" :src="company.mark_url" alt="" aria-hidden="true" />
      <h1 class="tfc__title">Two-step verification</h1>
      <p class="tfc__subtitle">
        {{
          useRecovery
            ? 'Enter one of your recovery codes to continue.'
            : 'Enter the 6-digit code from your authenticator app.'
        }}
      </p>

      <div v-if="errorMessage" class="tfc__alert" role="alert">{{ errorMessage }}</div>

      <form class="tfc__form" @submit.prevent="submit">
        <input
          v-if="!useRecovery"
          v-model="code"
          type="text"
          inputmode="numeric"
          maxlength="6"
          class="tfc__input"
          placeholder="123456"
          autocomplete="one-time-code"
          aria-label="Authentication code"
          autofocus
        />
        <input
          v-else
          v-model="recoveryCode"
          type="text"
          class="tfc__input"
          placeholder="xxxxxxxx-xxxxxxxx"
          autocomplete="one-time-code"
          aria-label="Recovery code"
          autofocus
        />

        <label class="tfc__trust">
          <input v-model="trustDevice" type="checkbox" class="tfc__trust-box" />
          <span>Trust this device for 30 days</span>
        </label>

        <button type="submit" class="tfc__submit" :disabled="!canSubmit || isLoading">
          {{ isLoading ? 'Verifying…' : 'Verify' }}
        </button>
      </form>

      <button type="button" class="tfc__link" @click="toggleMode">
        {{ useRecovery ? 'Use an authenticator code instead' : 'Use a recovery code instead' }}
      </button>
    </section>
  </div>
</template>

<style scoped>
.tfc {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: var(--space-6);
  background: var(--bg-app);
  font-family: var(--font-sans);
}

.tfc__card {
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

.tfc__mark {
  width: var(--space-12);
  height: var(--space-12);
  object-fit: contain;
  margin-bottom: var(--space-2);
}

.tfc__title {
  margin: 0;
  font-size: var(--text-2xl);
  font-weight: var(--font-bold);
  color: var(--text-primary);
}

.tfc__subtitle {
  margin: 0;
  color: var(--text-secondary);
  font-size: var(--text-sm);
}

.tfc__alert {
  width: 100%;
  padding: var(--space-3) var(--space-4);
  border-radius: var(--radius-md);
  border: 1px solid color-mix(in srgb, var(--accent-error) 40%, transparent);
  background: color-mix(in srgb, var(--accent-error) 12%, transparent);
  color: var(--accent-error);
  font-size: var(--text-sm);
}

.tfc__form {
  width: 100%;
  display: flex;
  flex-direction: column;
  gap: var(--space-3);
  margin-top: var(--space-2);
}

.tfc__input {
  width: 100%;
  padding: var(--space-3) var(--space-4);
  border-radius: var(--radius-md);
  border: 1px solid var(--border-strong);
  background: var(--bg-elevated);
  color: var(--text-primary);
  font-family: var(--font-mono);
  font-size: var(--text-lg);
  text-align: center;
  letter-spacing: 0.2em;
}

.tfc__input:focus {
  outline: none;
  border-color: var(--accent-primary);
}

.tfc__trust {
  display: flex;
  align-items: center;
  gap: var(--space-2);
  color: var(--text-secondary);
  font-size: var(--text-sm);
  cursor: pointer;
  user-select: none;
}

.tfc__trust-box {
  width: var(--space-4);
  height: var(--space-4);
  accent-color: var(--accent-primary);
  cursor: pointer;
}

.tfc__submit {
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

.tfc__submit:hover {
  opacity: 0.92;
}

.tfc__submit:disabled {
  opacity: 0.6;
  pointer-events: none;
}

.tfc__link {
  background: none;
  border: none;
  color: var(--accent-primary);
  font-size: var(--text-sm);
  cursor: pointer;
  padding: var(--space-1);
}
</style>

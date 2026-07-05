<script setup lang="ts">
/**
 * Two-factor authentication management. Wraps Fortify's JSON endpoints:
 *   · Enable   → POST /user/two-factor-authentication  (then scan QR)
 *   · Confirm  → POST /user/confirmed-two-factor-authentication { code }
 *   · Disable  → DELETE /user/two-factor-authentication
 *   · QR/secret/recovery codes → GET/POST /user/two-factor-*
 * All management endpoints are password-confirmed (Fortify confirmPassword),
 * so destructive actions first prompt for the account password via
 * POST /user/confirm-password.
 */
import { Head, router } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import { useToast } from 'primevue/usetoast';
import AppLayout from '@/pages/layouts/AppLayout.vue';
import AppHeader from '@/modules/app/components/AppHeader.vue';
import Button from '@/volt/Button.vue';
import SecondaryButton from '@/volt/SecondaryButton.vue';
import InputText from '@/volt/InputText.vue';
import Dialog from '@/volt/Dialog.vue';
import { apiFetch, HttpError } from '@/lib/http';

interface TrustedDevice {
  uuid: string;
  user_agent: string | null;
  ip_address: string | null;
  last_used_at: string | null;
  expires_at: string;
}

const props = defineProps<{
  enabled: boolean;
  confirmed: boolean;
  trustedDevices: TrustedDevice[];
}>();

defineOptions({ layout: AppLayout });

const toast = useToast();

type Status = 'disabled' | 'pending' | 'enabled';
const status = ref<Status>(props.enabled ? (props.confirmed ? 'enabled' : 'pending') : 'disabled');

const busy = ref<boolean>(false);
const qrSvg = ref<string>('');
const secretKey = ref<string>('');
const confirmCode = ref<string>('');
const confirmError = ref<string>('');
const recoveryCodes = ref<string[]>([]);
const showRecovery = ref<boolean>(false);

// ── Password-confirmation modal ──
const passwordModalOpen = ref<boolean>(false);
const passwordInput = ref<string>('');
const passwordError = ref<string>('');
let pendingAction: (() => Promise<void>) | null = null;

function fail(message: string): void {
  toast.add({ severity: 'error', summary: message, life: 5000 });
}

function requirePassword(action: () => Promise<void>): void {
  pendingAction = action;
  passwordInput.value = '';
  passwordError.value = '';
  passwordModalOpen.value = true;
}

async function submitPassword(): Promise<void> {
  passwordError.value = '';
  try {
    await apiFetch('POST', '/user/confirm-password', { password: passwordInput.value });
    passwordModalOpen.value = false;
    const action = pendingAction;
    pendingAction = null;
    if (action) {
      await action();
    }
  } catch (error) {
    passwordError.value =
      error instanceof HttpError && error.status === 422
        ? 'Incorrect password. Please try again.'
        : 'Could not verify your password.';
  }
}

async function loadEnrollment(): Promise<void> {
  const [qr, secret] = await Promise.all([
    apiFetch<{ svg: string }>('GET', '/user/two-factor-qr-code'),
    apiFetch<{ secretKey: string }>('GET', '/user/two-factor-secret-key'),
  ]);
  qrSvg.value = qr.svg;
  secretKey.value = secret.secretKey;
  await loadRecoveryCodes();
}

async function loadRecoveryCodes(): Promise<void> {
  recoveryCodes.value = await apiFetch<string[]>('GET', '/user/two-factor-recovery-codes');
}

function enable(): void {
  requirePassword(async () => {
    busy.value = true;
    try {
      await apiFetch('POST', '/user/two-factor-authentication');
      await loadEnrollment();
      status.value = 'pending';
    } catch {
      fail('Could not start two-factor setup.');
    } finally {
      busy.value = false;
    }
  });
}

async function confirm(): Promise<void> {
  if (confirmCode.value.length < 6) {
    return;
  }
  confirmError.value = '';
  busy.value = true;
  try {
    await apiFetch('POST', '/user/confirmed-two-factor-authentication', { code: confirmCode.value });
    status.value = 'enabled';
    confirmCode.value = '';
    showRecovery.value = true;
    toast.add({ severity: 'success', summary: 'Two-factor authentication enabled.', life: 4000 });
    router.reload({ only: ['enabled', 'confirmed'] });
  } catch (error) {
    confirmError.value =
      error instanceof HttpError && error.status === 422
        ? 'That code is invalid or expired.'
        : 'Could not confirm the code.';
  } finally {
    busy.value = false;
  }
}

function disable(): void {
  requirePassword(async () => {
    busy.value = true;
    try {
      await apiFetch('DELETE', '/user/two-factor-authentication');
      status.value = 'disabled';
      qrSvg.value = '';
      secretKey.value = '';
      recoveryCodes.value = [];
      showRecovery.value = false;
      toast.add({ severity: 'success', summary: 'Two-factor authentication disabled.', life: 4000 });
      router.reload({ only: ['enabled', 'confirmed'] });
    } catch {
      fail('Could not disable two-factor authentication.');
    } finally {
      busy.value = false;
    }
  });
}

function regenerateRecoveryCodes(): void {
  requirePassword(async () => {
    busy.value = true;
    try {
      await apiFetch('POST', '/user/two-factor-recovery-codes');
      await loadRecoveryCodes();
      showRecovery.value = true;
      toast.add({ severity: 'success', summary: 'Recovery codes regenerated.', life: 4000 });
    } catch {
      fail('Could not regenerate recovery codes.');
    } finally {
      busy.value = false;
    }
  });
}

async function revealRecoveryCodes(): Promise<void> {
  try {
    if (recoveryCodes.value.length === 0) {
      await loadRecoveryCodes();
    }
    showRecovery.value = true;
  } catch {
    fail('Could not load recovery codes.');
  }
}

function revokeDevice(uuid: string): void {
  router.delete(`/two-factor/trusted-devices/${uuid}`, { preserveScroll: true });
}

function deviceLabel(userAgent: string | null): string {
  if (!userAgent) {
    return 'Unknown device';
  }
  return userAgent.length > 60 ? `${userAgent.slice(0, 60)}…` : userAgent;
}

onMounted(() => {
  // Resume an interrupted enrollment (enabled but never confirmed).
  if (status.value === 'pending') {
    loadEnrollment().catch(() => fail('Could not load your two-factor setup.'));
  }
});
</script>

<template>
  <Head title="Two-factor authentication" />

  <AppHeader
    title="Two-factor authentication"
    subtitle="Add a second step to your sign-in for extra security."
  />

  <section class="tfa">
    <!-- Status banner -->
    <div class="tfa__status" :class="`tfa__status--${status}`">
      <i
        class="pi"
        :class="status === 'enabled' ? 'pi-shield' : 'pi-exclamation-triangle'"
        aria-hidden="true"
      />
      <div>
        <p class="tfa__status-title">
          {{ status === 'enabled' ? 'Two-factor authentication is on' : 'Two-factor authentication is off' }}
        </p>
        <p class="tfa__status-text">
          {{
            status === 'enabled'
              ? 'Your account requires an authenticator code at sign-in.'
              : 'Protect your account with a time-based one-time code.'
          }}
        </p>
      </div>
    </div>

    <!-- DISABLED → enable -->
    <div v-if="status === 'disabled'" class="tfa__card">
      <p class="tfa__lead">
        You'll use an authenticator app (Google Authenticator, 1Password, Authy…) to generate
        codes.
      </p>
      <Button label="Enable two-factor authentication" :disabled="busy" @click="enable" />
    </div>

    <!-- PENDING → scan + confirm -->
    <div v-else-if="status === 'pending'" class="tfa__card">
      <p class="tfa__lead">Scan this QR code with your authenticator app, then enter the 6-digit code.</p>
      <div class="tfa__qr" v-html="qrSvg" />
      <p v-if="secretKey" class="tfa__secret">
        Or enter this key manually: <code>{{ secretKey }}</code>
      </p>

      <form class="tfa__confirm" @submit.prevent="confirm">
        <InputText
          v-model="confirmCode"
          inputmode="numeric"
          maxlength="6"
          placeholder="123456"
          autocomplete="one-time-code"
          aria-label="Authentication code"
        />
        <Button type="submit" label="Confirm" :disabled="busy || confirmCode.length < 6" />
      </form>
      <p v-if="confirmError" class="tfa__error">{{ confirmError }}</p>
    </div>

    <!-- ENABLED → recovery codes + disable -->
    <div v-else class="tfa__card">
      <div class="tfa__recovery-head">
        <div>
          <p class="tfa__lead">Recovery codes</p>
          <p class="tfa__hint">Store these somewhere safe — each one signs you in once if you lose your device.</p>
        </div>
        <SecondaryButton
          v-if="!showRecovery"
          label="Show recovery codes"
          @click="revealRecoveryCodes"
        />
      </div>

      <ul v-if="showRecovery && recoveryCodes.length" class="tfa__codes">
        <li v-for="code in recoveryCodes" :key="code"><code>{{ code }}</code></li>
      </ul>

      <div class="tfa__actions">
        <SecondaryButton label="Regenerate recovery codes" :disabled="busy" @click="regenerateRecoveryCodes" />
        <button type="button" class="btn-danger" :disabled="busy" @click="disable">
          Disable two-factor
        </button>
      </div>
    </div>

    <!-- Trusted devices -->
    <div v-if="trustedDevices.length" class="tfa__card">
      <p class="tfa__lead">Trusted devices</p>
      <p class="tfa__hint">These devices skip the second step for 30 days.</p>
      <ul class="tfa__devices">
        <li v-for="device in trustedDevices" :key="device.uuid" class="tfa__device">
          <div class="tfa__device-body">
            <span class="tfa__device-name">{{ deviceLabel(device.user_agent) }}</span>
            <span class="tfa__device-meta">{{ device.ip_address ?? 'Unknown IP' }}</span>
          </div>
          <SecondaryButton label="Revoke" @click="revokeDevice(device.uuid)" />
        </li>
      </ul>
    </div>
  </section>

  <!-- Password confirmation -->
  <Dialog
    v-model:visible="passwordModalOpen"
    modal
    header="Confirm your password"
  >
    <form class="tfa__password" @submit.prevent="submitPassword">
      <p class="tfa__hint">For your security, please confirm your password to continue.</p>
      <InputText
        v-model="passwordInput"
        type="password"
        placeholder="Password"
        autocomplete="current-password"
        aria-label="Password"
        autofocus
      />
      <p v-if="passwordError" class="tfa__error">{{ passwordError }}</p>
    </form>
    <template #footer>
      <SecondaryButton label="Cancel" @click="passwordModalOpen = false" />
      <Button label="Confirm" @click="submitPassword" />
    </template>
  </Dialog>
</template>

<style scoped>
.tfa {
  max-width: 640px;
  display: flex;
  flex-direction: column;
  gap: var(--space-5);
}

.tfa__status {
  display: flex;
  align-items: center;
  gap: var(--space-4);
  padding: var(--space-4) var(--space-5);
  border-radius: var(--radius-xl);
  border: 1px solid var(--border-default);
}

.tfa__status .pi {
  font-size: var(--text-2xl);
}

.tfa__status--enabled {
  border-color: color-mix(in srgb, var(--accent-success) 40%, transparent);
  background: color-mix(in srgb, var(--accent-success) 10%, transparent);
  color: var(--accent-success);
}

.tfa__status--disabled,
.tfa__status--pending {
  border-color: color-mix(in srgb, var(--accent-warning) 40%, transparent);
  background: color-mix(in srgb, var(--accent-warning) 10%, transparent);
  color: var(--accent-warning);
}

.tfa__status-title {
  margin: 0;
  font-weight: var(--font-semibold);
  color: var(--text-primary);
}

.tfa__status-text {
  margin: var(--space-1) 0 0;
  color: var(--text-secondary);
  font-size: var(--text-sm);
}

.tfa__card {
  display: flex;
  flex-direction: column;
  gap: var(--space-4);
  padding: var(--space-5);
  border: 1px solid var(--border-default);
  border-radius: var(--radius-xl);
  background: color-mix(in srgb, var(--bg-surface) 55%, transparent);
}

.tfa__lead {
  margin: 0;
  font-weight: var(--font-semibold);
  color: var(--text-primary);
}

.tfa__hint {
  margin: 0;
  color: var(--text-muted);
  font-size: var(--text-sm);
}

.tfa__qr {
  align-self: center;
  padding: var(--space-4);
  background: #fff;
  border-radius: var(--radius-lg);
}

.tfa__qr :deep(svg) {
  display: block;
  width: 180px;
  height: 180px;
}

.tfa__secret {
  margin: 0;
  color: var(--text-secondary);
  font-size: var(--text-sm);
  word-break: break-all;
}

.tfa__secret code,
.tfa__codes code,
.tfa__device-meta {
  font-family: var(--font-mono);
}

.tfa__confirm {
  display: flex;
  gap: var(--space-3);
  flex-wrap: wrap;
}

.tfa__recovery-head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: var(--space-3);
}

.tfa__codes {
  list-style: none;
  margin: 0;
  padding: var(--space-4);
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
  gap: var(--space-2);
  border-radius: var(--radius-lg);
  background: color-mix(in srgb, var(--bg-elevated) 60%, transparent);
}

.tfa__codes code {
  color: var(--text-primary);
  font-size: var(--text-sm);
}

.tfa__actions {
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-3);
}

.tfa__devices {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: var(--space-2);
}

.tfa__device {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-3);
  padding: var(--space-3) var(--space-4);
  border: 1px solid var(--border-subtle);
  border-radius: var(--radius-lg);
}

.tfa__device-body {
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.tfa__device-name {
  color: var(--text-primary);
  font-size: var(--text-sm);
  overflow-wrap: anywhere;
}

.tfa__device-meta {
  color: var(--text-muted);
  font-size: var(--text-xs);
}

.tfa__password {
  display: flex;
  flex-direction: column;
  gap: var(--space-3);
  width: min(22rem, calc(100vw - 4rem));
}

.tfa__error {
  margin: 0;
  color: var(--accent-error);
  font-size: var(--text-sm);
}

.btn-danger {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: var(--space-2) var(--space-3);
  border-radius: var(--radius-md);
  border: 1px solid var(--accent-error);
  background: var(--accent-error);
  color: var(--text-on-accent);
  font-weight: var(--font-medium);
  font-size: var(--text-sm);
  cursor: pointer;
  transition: opacity var(--transition-fast);
}

.btn-danger:hover {
  opacity: 0.9;
}

.btn-danger:disabled {
  opacity: 0.6;
  pointer-events: none;
}
</style>

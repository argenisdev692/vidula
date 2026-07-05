<script setup lang="ts">
/**
 * Active browser sessions — lists every device the account is signed in on
 * (backed by the `sessions` table via SessionRegistry) and lets the user revoke
 * a single device, sign out of all other devices, or sign out everywhere
 * (including the current device, which returns them to /login).
 */
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppLayout from '@/pages/layouts/AppLayout.vue';
import AppHeader from '@/modules/app/components/AppHeader.vue';
import SecondaryButton from '@/volt/SecondaryButton.vue';
import Dialog from '@/volt/Dialog.vue';

interface ActiveSession {
  id: string;
  ip_address: string | null;
  user_agent: string | null;
  last_active_at: string;
  is_current: boolean;
}

const props = defineProps<{ sessions: ActiveSession[] }>();

defineOptions({ layout: AppLayout });

const hasOthers = computed<boolean>(() => props.sessions.some((s) => !s.is_current));
const confirmAllOpen = ref<boolean>(false);

function deviceLabel(userAgent: string | null): string {
  if (!userAgent) {
    return 'Unknown device';
  }
  const browser =
    /Edg/.test(userAgent) ? 'Edge'
    : /OPR|Opera/.test(userAgent) ? 'Opera'
    : /Chrome/.test(userAgent) ? 'Chrome'
    : /Firefox/.test(userAgent) ? 'Firefox'
    : /Safari/.test(userAgent) ? 'Safari'
    : 'Browser';
  const os =
    /Windows/.test(userAgent) ? 'Windows'
    : /Macintosh|Mac OS/.test(userAgent) ? 'macOS'
    : /Android/.test(userAgent) ? 'Android'
    : /iPhone|iPad|iOS/.test(userAgent) ? 'iOS'
    : /Linux/.test(userAgent) ? 'Linux'
    : 'Unknown OS';
  return `${browser} · ${os}`;
}

function formatDate(iso: string): string {
  return new Date(iso).toLocaleString(undefined, {
    dateStyle: 'medium',
    timeStyle: 'short',
  });
}

function revoke(id: string): void {
  router.delete(`/sessions/${id}`, { preserveScroll: true });
}

function signOutOthers(): void {
  router.delete('/sessions/others', { preserveScroll: true });
}

function signOutEverywhere(): void {
  confirmAllOpen.value = false;
  router.delete('/sessions/all');
}
</script>

<template>
  <Head title="Active sessions" />

  <AppHeader
    title="Active sessions"
    subtitle="Devices currently signed in to your account."
  />

  <section class="sessions">
    <ul class="sessions__list">
      <li v-for="session in sessions" :key="session.id" class="session-card">
        <div class="session-card__icon" aria-hidden="true">
          <i class="pi pi-desktop" />
        </div>
        <div class="session-card__body">
          <div class="session-card__title">
            {{ deviceLabel(session.user_agent) }}
            <span v-if="session.is_current" class="session-card__badge">This device</span>
          </div>
          <div class="session-card__meta">
            <span>{{ session.ip_address ?? 'Unknown IP' }}</span>
            <span aria-hidden="true">•</span>
            <span>Last active {{ formatDate(session.last_active_at) }}</span>
          </div>
        </div>
        <div v-if="!session.is_current" class="session-card__action">
          <SecondaryButton label="Sign out" @click="revoke(session.id)" />
        </div>
      </li>
    </ul>

    <div class="sessions__footer">
      <SecondaryButton
        label="Sign out other devices"
        :disabled="!hasOthers"
        @click="signOutOthers"
      />
      <button type="button" class="btn-danger" @click="confirmAllOpen = true">
        Sign out everywhere
      </button>
    </div>
  </section>

  <Dialog
    v-model:visible="confirmAllOpen"
    modal
    header="Sign out everywhere?"
  >
    <p class="sessions__confirm">
      This signs you out on every device, including this one. You'll need to sign in again.
    </p>
    <template #footer>
      <SecondaryButton label="Cancel" @click="confirmAllOpen = false" />
      <button type="button" class="btn-danger" @click="signOutEverywhere">
        Sign out everywhere
      </button>
    </template>
  </Dialog>
</template>

<style scoped>
.sessions {
  max-width: 720px;
}

.sessions__list {
  list-style: none;
  margin: 0 0 var(--space-6);
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: var(--space-3);
}

.session-card {
  display: flex;
  align-items: center;
  gap: var(--space-4);
  padding: var(--space-4) var(--space-5);
  border: 1px solid var(--border-default);
  border-radius: var(--radius-xl);
  background: color-mix(in srgb, var(--bg-surface) 60%, transparent);
}

.session-card__icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: var(--space-12);
  height: var(--space-12);
  flex-shrink: 0;
  border-radius: var(--radius-lg);
  background: color-mix(in srgb, var(--accent-primary) 12%, transparent);
  color: var(--accent-primary);
  font-size: var(--text-xl);
}

.session-card__body {
  flex: 1;
  min-width: 0;
}

.session-card__title {
  display: flex;
  align-items: center;
  gap: var(--space-2);
  font-weight: var(--font-semibold);
  color: var(--text-primary);
}

.session-card__badge {
  padding: 2px var(--space-2);
  border-radius: var(--radius-sm);
  background: color-mix(in srgb, var(--accent-success) 18%, transparent);
  color: var(--accent-success);
  font-size: var(--text-xs);
  font-weight: var(--font-medium);
}

.session-card__meta {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: var(--space-2);
  margin-top: var(--space-1);
  color: var(--text-muted);
  font-size: var(--text-sm);
}

.session-card__action {
  flex-shrink: 0;
}

.sessions__footer {
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-3);
  justify-content: flex-end;
}

.sessions__confirm {
  margin: 0;
  color: var(--text-secondary);
  font-size: var(--text-sm);
  width: min(24rem, calc(100vw - 4rem));
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

@media (max-width: 600px) {
  .session-card {
    flex-wrap: wrap;
  }

  .session-card__action {
    width: 100%;
  }

  .sessions__footer {
    justify-content: stretch;
  }
}
</style>

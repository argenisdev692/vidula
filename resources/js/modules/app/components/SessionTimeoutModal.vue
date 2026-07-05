<script setup lang="ts">
/**
 * Idle-session guard. After 30 minutes of no user activity it raises a warning
 * modal with a 2-minute countdown. "Stay signed in" refreshes the session
 * (partial Inertia reload) and re-arms the idle timer; letting the countdown
 * reach zero posts to /session/idle-logout, which signs the user out, stores the
 * current path as the intended return URL, and drops them on /login with the
 * "session expired" banner. Mounted once by AppLayout — no wiring required.
 */
import Button from '@/volt/Button.vue';
import SecondaryButton from '@/volt/SecondaryButton.vue';
import Dialog from '@/volt/Dialog.vue';
import type { SharedProps } from '@/types/inertia';
import { router, usePage } from '@inertiajs/vue3';
import { useIdle, useIntervalFn } from '@vueuse/core';
import { computed, ref, watch } from 'vue';

const IDLE_TIMEOUT_MS = 30 * 60 * 1000; // 30 minutes of inactivity → warn.
const COUNTDOWN_SECONDS = 120; // 2 minutes to act before forced logout.

const page = usePage<SharedProps>();
const isAuthenticated = computed<boolean>(() => page.props.auth.user !== null);

const { idle } = useIdle(IDLE_TIMEOUT_MS);

const showWarning = ref<boolean>(false);
const secondsLeft = ref<number>(COUNTDOWN_SECONDS);

const countdownLabel = computed<string>(() => {
  const minutes = Math.floor(secondsLeft.value / 60);
  const seconds = secondsLeft.value % 60;
  return `${minutes}:${seconds.toString().padStart(2, '0')}`;
});

const { pause, resume } = useIntervalFn(
  () => {
    secondsLeft.value -= 1;
    if (secondsLeft.value <= 0) {
      logoutNow();
    }
  },
  1000,
  { immediate: false },
);

watch(idle, (isIdle) => {
  if (isIdle && isAuthenticated.value && !showWarning.value) {
    secondsLeft.value = COUNTDOWN_SECONDS;
    showWarning.value = true;
    resume();
  }
});

function stay(): void {
  pause();
  showWarning.value = false;
  secondsLeft.value = COUNTDOWN_SECONDS;
  // A partial reload touches the server session so its rolling lifetime resets;
  // the click itself already resets the client-side idle detector.
  router.reload({ only: ['auth'] });
}

function logoutNow(): void {
  pause();
  showWarning.value = false;
  router.post(
    '/session/idle-logout',
    { intended: window.location.pathname + window.location.search },
    { preserveScroll: true },
  );
}
</script>

<template>
  <Dialog
    v-model:visible="showWarning"
    modal
    :closable="false"
    :draggable="false"
    :dismissable-mask="false"
    :close-on-escape="false"
    header="Still there?"
  >
    <div class="session-timeout" role="alertdialog" aria-live="assertive">
      <div class="session-timeout__icon" aria-hidden="true">
        <i class="pi pi-clock" />
      </div>
      <p class="session-timeout__lead">
        You've been inactive for a while. For your security, we'll sign you out in
      </p>
      <p class="session-timeout__countdown" aria-live="polite">{{ countdownLabel }}</p>
      <p class="session-timeout__hint">Choose "Stay signed in" to keep working.</p>
    </div>

    <template #footer>
      <SecondaryButton label="Log out now" @click="logoutNow" />
      <Button label="Stay signed in" autofocus @click="stay" />
    </template>
  </Dialog>
</template>

<style scoped>
.session-timeout {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  gap: var(--space-3);
  padding: var(--space-2) 0;
  width: min(22rem, calc(100vw - 4rem));
}

.session-timeout__icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: var(--space-16);
  height: var(--space-16);
  border-radius: 50%;
  background: color-mix(in srgb, var(--accent-warning) 15%, transparent);
  color: var(--accent-warning);
  font-size: var(--text-2xl);
}

.session-timeout__lead {
  margin: 0;
  color: var(--text-secondary);
  font-size: var(--text-sm);
}

.session-timeout__countdown {
  margin: 0;
  font-family: var(--font-mono);
  font-size: var(--text-3xl);
  font-weight: var(--font-bold);
  color: var(--accent-warning);
  font-variant-numeric: tabular-nums;
}

.session-timeout__hint {
  margin: 0;
  color: var(--text-muted);
  font-size: var(--text-xs);
}
</style>

<script setup lang="ts">
/**
 * Public (guest) contact form — standalone page (no AppLayout), mirroring the
 * guest-page precedent in pages/Auth/Login.vue (raw token-styled inputs).
 *
 * Anti-spam is layered:
 *   1. spatie/laravel-honeypot — the two hidden trap fields below (a randomized
 *      empty field + an encrypted "valid_from" timestamp) are injected into the
 *      POST payload via useForm().transform(). The ProtectAgainstSpam middleware
 *      silently drops any submission that fills the trap or posts too fast.
 *   2. Per-IP throttle (throttle:5,1) on the route.
 *   3. Server-side SpamGuard content scoring — flags (never rejects) spammy
 *      content for admin review. The sender always sees success.
 */
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useCompany } from '@/modules/app/composables/useCompany';
import type { SharedProps } from '@/types/inertia';
import type { HoneypotProps } from '@/modules/contact-support/types';

const props = defineProps<{ honeypot: HoneypotProps }>();

const page = usePage<SharedProps>();
const company = useCompany();

const documentTitle = computed<string>(() => `Contact us — ${company.value.name}`);
const currentYear = computed<number>(() => new Date().getFullYear());

/** Server flash set by PublicContactController::store on a successful visit. */
const flashSuccess = computed<string | null>(() => page.props.flash?.success ?? null);

const form = useForm({
    first_name: '',
    last_name: '',
    email: '',
    phone: '',
    subject: '',
    message: '',
    sms_consent: false,
    // Honeypot trap value — must stay '' for humans. A bot that fills the
    // off-screen input flips this via v-model and gets silently dropped.
    honeypot: '',
});

function submit(): void {
    form
        .transform((data) => {
            const { honeypot, ...fields } = data;

            return {
                ...fields,
                [props.honeypot.nameFieldName]: honeypot,
                [props.honeypot.validFromFieldName]: props.honeypot.encryptedValidFrom,
            };
        })
        .post('/contact', {
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
}
</script>

<template>
    <Head :title="documentTitle" />

    <div class="contact-page">
        <div class="contact-card">
            <header class="contact-head">
                <img
                    v-if="company.logo_url"
                    :src="company.logo_url"
                    :alt="company.name"
                    class="contact-logo"
                />
                <h1 class="contact-title">Contact {{ company.name }}</h1>
                <p class="contact-sub">
                    Have a question or need a hand? Send us a message and our team will get back to
                    you shortly.
                </p>
            </header>

            <div v-if="flashSuccess" class="contact-success" role="status">
                <i class="pi pi-check-circle" aria-hidden="true" />
                <span>{{ flashSuccess }}</span>
            </div>

            <form v-else class="contact-form" novalidate @submit.prevent="submit">
                <div class="grid-2">
                    <div class="field">
                        <label for="first_name">First name</label>
                        <input
                            id="first_name"
                            v-model="form.first_name"
                            type="text"
                            autocomplete="given-name"
                            maxlength="255"
                            :class="{ invalid: form.errors.first_name }"
                        />
                        <small v-if="form.errors.first_name" class="err">{{ form.errors.first_name }}</small>
                    </div>

                    <div class="field">
                        <label for="last_name">Last name</label>
                        <input
                            id="last_name"
                            v-model="form.last_name"
                            type="text"
                            autocomplete="family-name"
                            maxlength="255"
                            :class="{ invalid: form.errors.last_name }"
                        />
                        <small v-if="form.errors.last_name" class="err">{{ form.errors.last_name }}</small>
                    </div>
                </div>

                <div class="grid-2">
                    <div class="field">
                        <label for="email">Email</label>
                        <input
                            id="email"
                            v-model="form.email"
                            type="email"
                            autocomplete="email"
                            maxlength="255"
                            :class="{ invalid: form.errors.email }"
                        />
                        <small v-if="form.errors.email" class="err">{{ form.errors.email }}</small>
                    </div>

                    <div class="field">
                        <label for="phone">Phone</label>
                        <input
                            id="phone"
                            v-model="form.phone"
                            type="tel"
                            autocomplete="tel"
                            maxlength="20"
                            :class="{ invalid: form.errors.phone }"
                        />
                        <small v-if="form.errors.phone" class="err">{{ form.errors.phone }}</small>
                    </div>
                </div>

                <div class="field">
                    <label for="subject">Subject</label>
                    <input
                        id="subject"
                        v-model="form.subject"
                        type="text"
                        maxlength="150"
                        :class="{ invalid: form.errors.subject }"
                    />
                    <small v-if="form.errors.subject" class="err">{{ form.errors.subject }}</small>
                </div>

                <div class="field">
                    <label for="message">Message</label>
                    <textarea
                        id="message"
                        v-model="form.message"
                        rows="6"
                        maxlength="5000"
                        :class="{ invalid: form.errors.message }"
                    />
                    <small v-if="form.errors.message" class="err">{{ form.errors.message }}</small>
                </div>

                <label class="consent">
                    <input v-model="form.sms_consent" type="checkbox" />
                    <span>I agree to be contacted by SMS regarding my enquiry.</span>
                </label>

                <!-- Honeypot trap: off-screen, hidden from real users + AT. -->
                <div class="hp-field" aria-hidden="true">
                    <label :for="props.honeypot.nameFieldName">Leave this field empty</label>
                    <input
                        :id="props.honeypot.nameFieldName"
                        :name="props.honeypot.nameFieldName"
                        v-model="form.honeypot"
                        type="text"
                        tabindex="-1"
                        autocomplete="off"
                    />
                    <input
                        :name="props.honeypot.validFromFieldName"
                        :value="props.honeypot.encryptedValidFrom"
                        type="text"
                        tabindex="-1"
                        autocomplete="off"
                        readonly
                    />
                </div>

                <button type="submit" class="submit" :disabled="form.processing">
                    <i v-if="form.processing" class="pi pi-spin pi-spinner" aria-hidden="true" />
                    <span>{{ form.processing ? 'Sending…' : 'Send message' }}</span>
                </button>
            </form>

            <footer class="contact-foot">
                © {{ currentYear }} {{ company.name }}
            </footer>
        </div>
    </div>
</template>

<style scoped>
.contact-page {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 100vh;
    padding: var(--space-6);
    background: var(--bg-base, var(--bg-body));
    font-family: var(--font-sans);
}

.contact-card {
    width: 100%;
    max-width: 40rem;
    padding: var(--space-8);
    border: 1px solid var(--border-default);
    border-radius: var(--radius-lg);
    background: var(--bg-elevated);
    box-shadow: var(--shadow-lg, 0 10px 40px rgb(0 0 0 / 0.12));
}

.contact-head {
    text-align: center;
    margin-bottom: var(--space-6);
}

.contact-logo {
    height: 3rem;
    margin: 0 auto var(--space-4);
    object-fit: contain;
}

.contact-title {
    font-size: var(--text-2xl);
    font-weight: var(--font-bold);
    color: var(--text-primary);
}

.contact-sub {
    margin-top: var(--space-2);
    color: var(--text-secondary);
    font-size: var(--text-sm);
}

.contact-form {
    display: flex;
    flex-direction: column;
    gap: var(--space-4);
}

.grid-2 {
    display: grid;
    grid-template-columns: 1fr;
    gap: var(--space-4);
}

@media (min-width: 640px) {
    .grid-2 {
        grid-template-columns: 1fr 1fr;
    }
}

.field {
    display: flex;
    flex-direction: column;
    gap: var(--space-2);
}

.field label {
    font-size: var(--text-sm);
    font-weight: var(--font-medium);
    color: var(--text-secondary);
}

.field input,
.field textarea {
    width: 100%;
    padding: var(--space-3);
    border: 1px solid var(--border-default);
    border-radius: var(--radius-md);
    background: var(--bg-base, transparent);
    color: var(--text-primary);
    font-family: var(--font-sans);
    font-size: var(--text-sm);
    transition: border-color var(--transition);
}

.field input:focus,
.field textarea:focus {
    outline: none;
    border-color: var(--accent-primary);
    box-shadow: 0 0 0 3px color-mix(in srgb, var(--accent-primary) 20%, transparent);
}

.field input.invalid,
.field textarea.invalid {
    border-color: var(--accent-error);
}

.field textarea {
    resize: vertical;
}

.err {
    color: var(--accent-error);
    font-size: var(--text-xs);
}

.consent {
    display: flex;
    align-items: flex-start;
    gap: var(--space-2);
    color: var(--text-secondary);
    font-size: var(--text-sm);
    cursor: pointer;
}

.consent input {
    margin-top: 3px;
}

/* Honeypot: visually removed but present in the DOM for bots to trip. */
.hp-field {
    position: absolute;
    left: -9999px;
    top: -9999px;
    width: 1px;
    height: 1px;
    overflow: hidden;
    opacity: 0;
}

.submit {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: var(--space-2);
    padding: var(--space-3) var(--space-5);
    border: none;
    border-radius: var(--radius-md);
    background: var(--accent-primary);
    color: var(--accent-primary-contrast, #fff);
    font-family: var(--font-sans);
    font-size: var(--text-sm);
    font-weight: var(--font-semibold);
    cursor: pointer;
    transition: opacity var(--transition), transform var(--transition);
}

.submit:hover:not(:disabled) {
    transform: translateY(-1px);
}

.submit:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.contact-success {
    display: flex;
    align-items: center;
    gap: var(--space-3);
    padding: var(--space-5);
    border-radius: var(--radius-md);
    background: color-mix(in srgb, var(--accent-success) 12%, transparent);
    color: var(--accent-success);
    font-size: var(--text-sm);
    font-weight: var(--font-medium);
}

.contact-success .pi {
    font-size: var(--text-xl);
}

.contact-foot {
    margin-top: var(--space-6);
    text-align: center;
    color: var(--text-muted);
    font-size: var(--text-xs);
}
</style>

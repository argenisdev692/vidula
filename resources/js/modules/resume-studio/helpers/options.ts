import type { SelectOption } from '@/common/form/types';
import type { ApplicationStatus, AiProvider, LocationScope } from '../types';
import { LOCATION_SCOPE_OPTIONS } from './locationScopes';

export const APPLICATION_STATUS_OPTIONS: SelectOption[] = [
    { label: 'New', value: 'new' },
    { label: 'Saved', value: 'saved' },
    { label: 'Applied', value: 'applied' },
    { label: 'Skipped', value: 'skipped' },
    { label: 'Dismissed', value: 'dismissed' },
];

export const AI_PROVIDER_OPTIONS: SelectOption[] = [
    { label: 'OpenAI', value: 'openai' },
    { label: 'Anthropic', value: 'anthropic' },
    { label: 'Gemini', value: 'gemini' },
];

/** Stable location_scope enum values for Zod (DRY with LOCATION_SCOPE_OPTIONS). */
export const LOCATION_SCOPE_VALUES = LOCATION_SCOPE_OPTIONS.map(
    (option) => option.value as LocationScope,
) as [LocationScope, ...LocationScope[]];

export const AI_PROVIDER_VALUES = AI_PROVIDER_OPTIONS.map(
    (option) => option.value as AiProvider,
) as [AiProvider, ...AiProvider[]];

export function applicationStatusLabel(status: ApplicationStatus): string {
    const match = APPLICATION_STATUS_OPTIONS.find((option) => option.value === status);
    return match?.label ?? status;
}

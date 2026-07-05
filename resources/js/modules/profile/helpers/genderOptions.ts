import type { SelectOption } from '@/common/form/types';

/**
 * Gender choices for the profile form's SelectField. Values mirror the backend
 * allowlist in App\Actions\Fortify\UpdateUserProfileInformation::GENDERS.
 */
export const genderOptions: SelectOption[] = [
    { label: 'Male', value: 'male' },
    { label: 'Female', value: 'female' },
    { label: 'Other', value: 'other' },
    { label: 'Prefer not to say', value: 'prefer_not_to_say' },
];

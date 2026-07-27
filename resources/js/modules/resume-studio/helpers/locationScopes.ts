/**
 * LinkedIn-style location presets for Resume Studio job search.
 * Values are stable IDs sent to the backend (snake_case).
 */

import type { SelectOption } from '@/common/form/types';
import type { LocationScope, ResumeLanguage, SearchLanguage } from '@/modules/resume-studio/types';

export type { LocationScope, ResumeLanguage, SearchLanguage };

export const LOCATION_SCOPE_OPTIONS: SelectOption[] = [
    { label: 'Worldwide', value: 'worldwide' },
    { label: 'Remote (any region)', value: 'remote' },
    { label: 'Schengen Area', value: 'schengen' },
    { label: 'United States', value: 'united_states' },
    { label: 'United Kingdom', value: 'united_kingdom' },
    { label: 'Latin America', value: 'latin_america' },
    { label: 'Continent · Europe', value: 'europe' },
    { label: 'Continent · North America', value: 'north_america' },
    { label: 'Continent · South America', value: 'south_america' },
    { label: 'Continent · Africa', value: 'africa' },
    { label: 'Continent · Asia', value: 'asia' },
    { label: 'Continent · Oceania', value: 'oceania' },
    { label: 'Country · Portugal', value: 'portugal' },
    { label: 'Country · Spain', value: 'spain' },
    { label: 'Country · Germany', value: 'germany' },
    { label: 'Country · France', value: 'france' },
    { label: 'Country · Netherlands', value: 'netherlands' },
    { label: 'Country · Ireland', value: 'ireland' },
    { label: 'Country · Canada', value: 'canada' },
    { label: 'Country · Mexico', value: 'mexico' },
    { label: 'Country · Brazil', value: 'brazil' },
    { label: 'Country · Argentina', value: 'argentina' },
    { label: 'Country · Colombia', value: 'colombia' },
    { label: 'Country · Chile', value: 'chile' },
];

export const SEARCH_LANGUAGE_OPTIONS: SelectOption[] = [
    { label: 'Spanish', value: 'es' },
    { label: 'English', value: 'en' },
    { label: 'Spanish & English', value: 'both' },
];

export const RESUME_LANGUAGE_OPTIONS: SelectOption[] = [
    { label: 'English', value: 'en' },
    { label: 'Spanish', value: 'es' },
    { label: 'Portuguese (Portugal)', value: 'pt-PT' },
];

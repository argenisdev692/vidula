<?php

declare(strict_types=1);

namespace Modules\AiResumeStudio\Domain\Enums;

/**
 * Language of the refined CV Markdown / PDF export (not job-search language).
 * pt-PT = European Portuguese (Portugal), not Brazilian.
 */
enum ResumeLanguage: string
{
    case English = 'en';
    case Spanish = 'es';
    case PortuguesePortugal = 'pt-PT';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::English => 'English',
            self::Spanish => 'Spanish',
            self::PortuguesePortugal => 'Portuguese (Portugal)',
        };
    }

    /** Instruction block injected into refine / cover prompts. */
    public function outputInstruction(): string
    {
        return match ($this) {
            self::English => 'OUTPUT LANGUAGE (mandatory): Write the entire refined resume in English. Translate from the SOURCE CV if needed. Keep proper nouns (names, employers, product names) unchanged.',
            self::Spanish => 'OUTPUT LANGUAGE (mandatory): Write the entire refined resume in Spanish (neutral Latin American / international Spanish is fine unless SOURCE is clearly Peninsular). Translate from the SOURCE CV if needed. Keep proper nouns unchanged.',
            self::PortuguesePortugal => 'OUTPUT LANGUAGE (mandatory): Write the entire refined resume in European Portuguese (Portugal / pt-PT). Use Portugal orthography and vocabulary (e.g. "telemóvel", "equipa", "utilizador") — NOT Brazilian Portuguese. Translate from the SOURCE CV if needed. Keep proper nouns unchanged.',
        };
    }

    public function coverInstruction(): string
    {
        return match ($this) {
            self::English => 'OUTPUT LANGUAGE (mandatory): Write subject and body in English.',
            self::Spanish => 'OUTPUT LANGUAGE (mandatory): Write subject and body in Spanish.',
            self::PortuguesePortugal => 'OUTPUT LANGUAGE (mandatory): Write subject and body in European Portuguese (Portugal / pt-PT), not Brazilian.',
        };
    }

    public function digestInstruction(): string
    {
        return match ($this) {
            self::English => 'OUTPUT LANGUAGE (mandatory): Write the digest in English.',
            self::Spanish => 'OUTPUT LANGUAGE (mandatory): Write the digest in Spanish.',
            self::PortuguesePortugal => 'OUTPUT LANGUAGE (mandatory): Write the digest in European Portuguese (Portugal / pt-PT), not Brazilian.',
        };
    }

    /** BCP 47 tag for HTML lang / PDF. */
    public function htmlLang(): string
    {
        return $this->value;
    }
}

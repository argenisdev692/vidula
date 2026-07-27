<?php

declare(strict_types=1);

namespace Modules\AiResumeStudio\Domain\Enums;

/**
 * Job-search geography presets (LinkedIn-style scopes).
 * Values must stay in sync with the frontend locationScopes helper.
 */
enum LocationScope: string
{
    case Worldwide = 'worldwide';
    case Remote = 'remote';
    case Schengen = 'schengen';
    case UnitedStates = 'united_states';
    case UnitedKingdom = 'united_kingdom';
    case LatinAmerica = 'latin_america';
    case Europe = 'europe';
    case NorthAmerica = 'north_america';
    case SouthAmerica = 'south_america';
    case Africa = 'africa';
    case Asia = 'asia';
    case Oceania = 'oceania';
    case Portugal = 'portugal';
    case Spain = 'spain';
    case Germany = 'germany';
    case France = 'france';
    case Netherlands = 'netherlands';
    case Ireland = 'ireland';
    case Canada = 'canada';
    case Mexico = 'mexico';
    case Brazil = 'brazil';
    case Argentina = 'argentina';
    case Colombia = 'colombia';
    case Chile = 'chile';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /** Fragment appended to Tavily job-search queries. */
    public function searchFragment(): string
    {
        return match ($this) {
            self::Worldwide => 'worldwide jobs',
            self::Remote => 'remote jobs',
            self::Schengen => 'Schengen Area Europe jobs',
            self::UnitedStates => 'United States jobs',
            self::UnitedKingdom => 'United Kingdom jobs',
            self::LatinAmerica => 'Latin America LatAm jobs',
            self::Europe => 'Europe jobs',
            self::NorthAmerica => 'North America jobs',
            self::SouthAmerica => 'South America jobs',
            self::Africa => 'Africa jobs',
            self::Asia => 'Asia jobs',
            self::Oceania => 'Oceania Australia New Zealand jobs',
            self::Portugal => 'Portugal jobs',
            self::Spain => 'Spain jobs',
            self::Germany => 'Germany jobs',
            self::France => 'France jobs',
            self::Netherlands => 'Netherlands jobs',
            self::Ireland => 'Ireland jobs',
            self::Canada => 'Canada jobs',
            self::Mexico => 'Mexico jobs',
            self::Brazil => 'Brazil jobs',
            self::Argentina => 'Argentina jobs',
            self::Colombia => 'Colombia jobs',
            self::Chile => 'Chile jobs',
        };
    }
}

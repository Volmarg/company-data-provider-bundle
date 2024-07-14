<?php

namespace CompanyDataProvider\Enum\CountryCode;

enum Iso3166CountryCodeEnum: string
{
    case GERMANY_3_DIGIT = "DEU";
    case GERMANY_2_DIGIT = "DE";

    case POLAND_3_DIGIT  = "POL";
    case POLAND_2_DIGIT  = "PL";

    case FRANCE_3_DIGIT  = "FRA";
    case FRANCE_2_DIGIT  = "FR";

    case SPAIN_3_DIGIT  = "ESP";
    case SPAIN_2_DIGIT  = "ES";

    case SWEDISH_3_DIGIT  = "SWE";
    case SWEDISH_2_DIGIT  = "SW";

    case NORWAY_3_DIGIT  = "NOR";
    case NORWAY_2_DIGIT  = "NO";

    /**
     * @param string $code
     *
     * @return string|null
     */
    public static function get2digitFor3digit(string $code): ?string
    {
        return match (strtolower($code)) {
            strtolower(self::GERMANY_3_DIGIT->value) => self::GERMANY_2_DIGIT->value,
            strtolower(self::POLAND_3_DIGIT->value)  => self::POLAND_2_DIGIT->value,
            strtolower(self::FRANCE_3_DIGIT->value)  => self::FRANCE_2_DIGIT->value,
            strtolower(self::SPAIN_3_DIGIT->value)   => self::SPAIN_2_DIGIT->value,
            strtolower(self::SWEDISH_3_DIGIT->value) => self::SWEDISH_2_DIGIT->value,
            strtolower(self::NORWAY_3_DIGIT->value)  => self::NORWAY_2_DIGIT->value,
            default => null,
        };
    }
}
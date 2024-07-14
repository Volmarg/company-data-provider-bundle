<?php

namespace CompanyDataProvider\Service\Provider\Email\SearchEngineStrings;

use CompanyDataProvider\Enum\CountryCode\Iso3166CountryCodeEnum;
use CompanyDataProvider\Service\Provider\Email\EmailProviderService;

/**
 * - {@see EmailProviderService::searchStringByString}
 */
class AppendedStringProvider
{
    /**
     * Providing this to the {@see AppendedStringProvider::getSearchEngineFindEmailAppendedStrings}
     * returns the {@see AppendedStringConstants::GENERIC_STRINGS}
     */
    public const CODE_UNDEFINED = "undefined";

    /**
     * Can be used to provide searched append-able strings
     *
     * {@see CODE_UNDEFINED} which covers special case of returning generic strings,
     * also in case when the code is not supported it will fall back to the same value
     * that this constant would return
     *
     * @param string|null $threeDigitIsoCode
     *
     * @return array
     */
    public static function getAppendedStrings(?string $threeDigitIsoCode = null): array
    {
        if (!empty($threeDigitIsoCode)) {
            $threeDigitIsoCode = strtoupper($threeDigitIsoCode);
        }

        $languageStrings = match ($threeDigitIsoCode) {
            Iso3166CountryCodeEnum::GERMANY_3_DIGIT->value => AppendedStringConstants::GERMAN_STRINGS,
            Iso3166CountryCodeEnum::POLAND_3_DIGIT->value  => AppendedStringConstants::POLISH_STRINGS,
            Iso3166CountryCodeEnum::FRANCE_3_DIGIT->value  => AppendedStringConstants::FRENCH_STRINGS,
            Iso3166CountryCodeEnum::SWEDISH_3_DIGIT->value => AppendedStringConstants::SWEDISH_STRINGS,
            Iso3166CountryCodeEnum::SPAIN_3_DIGIT->value   => AppendedStringConstants::SPANISH_STRINGS,
            Iso3166CountryCodeEnum::NORWAY_3_DIGIT->value  => AppendedStringConstants::NORWAY_STRINGS,
            default                                        => AppendedStringConstants::GENERIC_STRINGS,
        };

        return array_values(array_unique([
            ...AppendedStringConstants::TOP_PRIORITY_GENERIC_STRING,
            ...$languageStrings,
            ...AppendedStringConstants::LOWER_PRIORITY_GENERIC_STRING,
        ]));
    }

}
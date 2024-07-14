<?php

namespace CompanyDataProvider\Service\Parser;

class CompanyLocationParser
{
    /**
     * Will remove any obsolete characters, anything that is useless.
     * For example, it turned out that locations without any numbers etc. yield
     * better results when looking for company emails.
     *
     * This does improve things "a bit", because not every character / combination can be removed, example:
     * - "Venloer Straße 47-53" this is better than "Venloer Straße",
     * - "Paris 6e - 75" this is worse than "Paris",
     * - "Swarzędz" is better than "Swarzędz (Poznań - 10km)"
     *
     * @param string|null $locationName
     *
     * @return string|null
     */
    public static function clear(?string $locationName): ?string
    {
        if (empty($locationName)) {
            return $locationName;
        }

        $normalised     = $locationName;
        $cleanupRegexes = [
            ' [-–] .*',
            ' [-–]',
            '\(.*',
            '^[0-9]* ',
        ];

        foreach ($cleanupRegexes as $regex) {
            $normalised = preg_replace("#{$regex}#mi", '', trim($normalised));
        }

        return $normalised;
    }
}

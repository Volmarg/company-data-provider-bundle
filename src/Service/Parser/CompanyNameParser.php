<?php

namespace CompanyDataProvider\Service\Parser;

use CompanyDataProvider\Controller\Provider\CompanyDataProviderInterface;

/**
 * Handles processing company name
 */
class CompanyNameParser
{

    /**
     * Takes company name and returns array with a bunch of variants sucha as:
     * - acronym,
     * - short strings stripped ("of", "to", etc.)
     * - kebab cased version etc.
     *
     * @param string $companyName
     *
     * @return array
     */
    public function getAllVariants(string $companyName): array
    {
        $longWordsVariant = $this->getWithLongWordsOnly($companyName);

        $variants = [
            $this->getKebabCased($companyName),
            $this->getGluedName($companyName),
            $this->getKebabCased($longWordsVariant),
            $this->getGluedName($longWordsVariant),
            $longWordsVariant,

            /**
             * These must remain last as it's the worst case check,
             * Each of the long words will be used
             */
            ...$this->getAcronyms($longWordsVariant),
            ...$this->getAcronyms($companyName),
            ...explode(" ", $longWordsVariant),
        ];

        return array_values(array_filter(array_unique($variants)));
    }

    /**
     * @param string $companyName
     *
     * @return string
     */
    private function getKebabCased(string $companyName): string
    {
        return str_replace(" ", "-", $companyName);
    }

    /**
     * @param string $companyName
     *
     * @return string
     */
    private function getGluedName(string $companyName): string
    {
        return str_replace(" ", "", $companyName);
    }

    /**
     * @param string $companyName
     *
     * @return string
     */
    private function getWithLongWordsOnly(string $companyName): string
    {
        $namePartials  = explode(" ", $companyName);
        $filteredParts = array_filter($namePartials, function (string $part) {
            return (strlen($part) >= CompanyDataProviderInterface::COMPANY_PARTIAL_NAME_MIN_LENGTH);
        });

        return implode(" ", $filteredParts);
    }

    /**
     * Example outputs:
     * - Company: M & GT Consulting Space
     * - Output1: Mgt Consulting Space
     * - Output2: Mgtc space
     *
     * @param string $companyName
     *
     * @return array
     */
    private function getAcronyms(string $companyName): array
    {
        $acronyms = [];
        $allWords = explode(" ", $companyName);
        for ($repeatCount = 0; $repeatCount < count($allWords); $repeatCount++) {
            if ($repeatCount === 0) {
                $acronyms[] = $this->getAcronym($companyName);
                continue;
            }

            $targetString = "";
            for ($x = 0; $x < $repeatCount; $x++) {
                $targetString .= array_shift($allWords);
            }

            $targetString .= implode(" ", $allWords);
            if (!str_contains($targetString, " ")) {
                break;
            }

            $acronyms[] = $this->getAcronym($targetString);
            $allWords   = explode(" ", $companyName);
        }

        return $acronyms;
    }

    /**
     * @param string $companyName
     *
     * @return string
     */
    private function getAcronym(string $companyName): string
    {
        $acronym = '';
        if (preg_match_all('/\b(\w)/', $companyName, $matches)) {
            $acronym = implode('', $matches[1]);
        }

        return $acronym;
    }
}
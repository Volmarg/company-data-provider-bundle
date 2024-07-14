<?php

namespace CompanyDataProvider\Service\Parser;

use CompanyDataProvider\Controller\Provider\CompanyDataProviderInterface;

/**
 * Handles checking & validating company related data
 */
class CompanyInformationParser
{
    public function __construct(
        private readonly CompanyNameParser $companyNameParser
    ) {
    }

    /**
     * Will check if provided string is related to the company name by checking its similarity.
     * Basically saying: does the string consist of the company name in any way (either full company name, it's glued version etc.)
     *
     * @param string $checkedData
     * @param string $companyName
     * @return bool
     */
    public function isRelatedToCompanyName(string $checkedData, string $companyName): bool
    {
        $allVariants = $this->companyNameParser->getAllVariants($companyName);
        foreach ($allVariants as $variant) {
            similar_text(
                strtolower($variant),
                strtolower($checkedData),
                $similarityPercentage
            );

            if ($similarityPercentage >= CompanyDataProviderInterface::COMPANY_NAME_DOMAIN_MATCH_SIMILARITY_PERCENTAGE) {
                return true;
            }
        }

        return false;
    }

    /**
     * Will check if provided data contain the company name variant
     *
     * @param string $checkedData
     * @param string $companyName
     *
     * @return bool
     */
    public function containsCompanyName(string $checkedData, string $companyName): bool
    {
        $namePartials = explode(" ", $companyName);
        if (empty($namePartials)) {
            return false;
        }

        $firstPart = $namePartials[array_key_first($namePartials)];
        return (
                str_contains(strtolower($checkedData), strtolower($companyName))
            ||  str_contains(strtolower($checkedData), strtolower($firstPart))
        );
    }

}
<?php

namespace CompanyDataProvider\Service\Provider\Website;

use CompanyDataProvider\DTO\Clearbit\CompanyNameToDomainDto;

/**
 * @deprecated - remove that with references
 * Handles providing domain name for company name
 */
class ClearbitDomainProviderService
{

    /**
     * Will try to resolve the company domain by company name,
     * returns array as it's possible that more than 1 domain matches given name
     *
     * @param string $companyName
     * @return CompanyNameToDomainDto[]
     */
    public function getForCompanyName(string $companyName): array
    {
        throw new \Exception("Not supported yet");
    }

}
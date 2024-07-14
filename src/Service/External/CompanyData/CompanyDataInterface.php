<?php

namespace CompanyDataProvider\Service\External\CompanyData;

use CompanyDataProvider\DTO\CompanyDataService\OrganizationDataDto;
use WebScrapperBundle\Exception\MissingDependencyException;

/**
 * Describes common logic for external services for providing the organization/company data
 */
interface CompanyDataInterface
{
    /**
     * Will return company/organization data from external service
     *
     * @param string      $companyName
     * @param string|null $targetCountry3DigitIsoCode
     *
     * @return OrganizationDataDto|null
     *
     * @throws MissingDependencyException
     */
    public function getDataForCompanyName(string $companyName, ?string $targetCountry3DigitIsoCode = null): ?OrganizationDataDto;

    /**
     * Returns the name of the service used for providing the data
     *
     * @return string
     */
    public function getServiceName(): string;
}
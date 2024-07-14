<?php

namespace CompanyDataProvider\Service\ConfigLoader;

use CompanyDataProvider\Service\Provider\Website\ClearbitDomainProviderService;

/**
 * Handles loading configuration from yaml
 */
class ConfigLoader
{

    /**
     * Returns base url used later in the
     * - {@see LogoProvider}
     *
     * @var string $clearbitBaseUrl
     */
    private string $clearbitBaseUrl;

    /**
     * Returns the base url used to obtain company domain from company name
     * - {@see ClearbitDomainProviderService}
     *
     * @var string $clearbitCompanyNameToDomain
     */
    private string $clearbitCompanyNameToDomain;

    /**
     * @return string
     */
    public function getClearbitBaseUrl(): string
    {
        return $this->clearbitBaseUrl;
    }

    /**
     * @param string $clearbitBaseUrl
     */
    public function setClearbitBaseUrl(string $clearbitBaseUrl): void
    {
        $this->clearbitBaseUrl = $clearbitBaseUrl;
    }

    /**
     * @return string
     */
    public function getClearbitCompanyNameToDomain(): string
    {
        return $this->clearbitCompanyNameToDomain;
    }

    /**
     * @param string $clearbitCompanyNameToDomain
     */
    public function setClearbitCompanyNameToDomain(string $clearbitCompanyNameToDomain): void
    {
        $this->clearbitCompanyNameToDomain = $clearbitCompanyNameToDomain;
    }

}
<?php

namespace CompanyDataProvider\Service\Provider\Logo;

/**
 * Provides small logo of company
 * This tool uses domain name of company to provide its logo
 *
 * @link https://clearbit.com/logo
 * @example https://logo.clearbit.com/biedronka.pl
 */
class ClearbitLogoProviderService
{

    /**
     * Return logo for domain, if no logo will be found then null is returned
     *
     * @param string $domain
     * @return string|null
     */
    public function getForDomain(string $domain): ?string
    {
        //
    }

}
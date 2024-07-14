<?php

namespace CompanyDataProvider\Service\Provider\Website;

/**
 * Handles providing company website
 */
class CompanyWebsiteProviderService
{

    public function findAnyPage(string $companyName, ?string $companyLocation): ?string
    {

    }

    /**
     * @param string $url
     *
     * @return string
     */
    private function anyPageIntoHomePage(string $url): string
    {
        $host = parse_url($url, PHP_URL_HOST);
        return $host;
    }

}
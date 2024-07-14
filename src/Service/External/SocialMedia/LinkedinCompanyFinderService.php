<?php

namespace CompanyDataProvider\Service\External\SocialMedia;

use CompanyDataProvider\Service\Parser\CompanyInformationParser;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;
use SearchEngineProvider\Exception\SearchService\EngineNotSupported;
use SearchEngineProvider\Exception\SearchService\NoUsedEngineDefinedException;
use SearchEngineProvider\Service\Paid\Search\PaidEngineSearchInterface;
use SearchEngineProvider\Service\SearchEngine\Other\DuckDuck\DuckDuckEngineService;
use SearchEngineProvider\Service\SearchEngine\SimpleDomContent\BingEngineService;
use SearchEngineProvider\Service\SearchEngine\SimpleDomContent\DuckDuckHtmlEngineService;
use SearchEngineProvider\Service\SearchEngine\SimpleDomContent\YahooEngineService;
use SearchEngineProvider\Service\SearchService;

/**
 * Handles providing company link on {@link https://www.linkedin.com/}
 */
class LinkedinCompanyFinderService
{
    public function __construct(
        private SearchService                      $searchService,
        private CompanyInformationParser           $companyInformationParser,
        private LoggerInterface                    $logger,
        private readonly PaidEngineSearchInterface $paidEngineSearch
    ){
        $this->paidEngineSearch->setAcceptUsage(true);
        $this->searchService->setUsedEnginesFqns([
            DuckDuckEngineService::class,
            BingEngineService::class,
            DuckDuckHtmlEngineService::class,
            YahooEngineService::class,
        ]);
    }

    /**
     * Will return link to company linkedin profile, or null if no matches were found
     *
     * @param string      $companyName
     * @param string|null $targetCountry3DigitIsoCode
     *
     * @return string|null
     * @throws ContainerExceptionInterface
     * @throws EngineNotSupported
     * @throws NoUsedEngineDefinedException
     * @throws NotFoundExceptionInterface
     */
    public function findUrlForCompany(string $companyName, ?string $targetCountry3DigitIsoCode = null): ?string
    {
        $this->logger->info("Searching for linkedin company profile for company: {$companyName}");
        $linkedinUrl   = null;
        $searchResults = [];

        while (
            $this->searchService->hasNextEngine()
            && (is_null($linkedinUrl) || empty($searchResults))
        ) {
            $this->searchService->setNextEngine();

            $searchResults = $this->searchService->getFirstPageSearchResultLinks("{$companyName} linkedin");
            $linkedinUrl   = $this->getUrlFromSearchResults($searchResults, $companyName);
        }

        if (empty($linkedinUrl)) {
            $searchResults = $this->paidEngineSearch->getSearchResults("{$companyName} linkedin");
            $linkedinUrl   = $this->getUrlFromSearchResults($searchResults, $companyName);
        }

        $this->searchService->resetEngines();

        return $linkedinUrl;
    }

    /**
     * @param array  $searchResults
     * @param string $companyName
     *
     * @return string|null
     */
    private function getUrlFromSearchResults(array $searchResults, string $companyName): ?string
    {
        $companyLinkedinUrl = null;
        $anyLinkedinUrl     = null;

        foreach ($searchResults as $searchResult) {
            if (
                    !str_contains($searchResult->getLink(), "linkedin")
                ||
                (
                        !$this->companyInformationParser->containsCompanyName($searchResult->getDescription(), $companyName)
                    &&  !$this->companyInformationParser->containsCompanyName($searchResult->getLink(), $companyName)
                )
            ) {
                continue;
            }

            if (empty($anyLinkedinUrl)) {
                $anyLinkedinUrl = $searchResult->getLink();
            }

            if (
                    str_contains($searchResult->getLink(), "company")
                &&  empty($companyLinkedinUrl)
            ) {
                $companyLinkedinUrl = $searchResult->getLink();
            }
        }

        return $companyLinkedinUrl ?? $anyLinkedinUrl;
    }

}
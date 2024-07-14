<?php

namespace CompanyDataProvider\Service\External\CompanyData\DunAndBradstreet;

use CompanyDataProvider\DTO\CompanyDataService\OrganizationDataDto;
use CompanyDataProvider\Service\External\CompanyData\CompanyDataInterface;
use CompanyDataProvider\Service\Parser\CompanyInformationParser;
use CompanyDataProvider\Service\System\EnvReader;
use CompanyDataProvider\Service\Url\UrlHandlerService;
use DOMAttr;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use Psr\Log\LoggerInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Response;
use TypeError;
use WebScrapperBundle\Constants\UserAgentConstants;
use WebScrapperBundle\DTO\CrawlerConfigurationDto;
use WebScrapperBundle\Service\CrawlerEngine\CrawlerEngineServiceInterface;
use WebScrapperBundle\Service\CrawlerEngine\RawCurlCrawlerEngineService;
use WebScrapperBundle\Service\CrawlerService;
use WebScrapperBundle\Service\Request\Guzzle\GuzzleInterface;
use WebScrapperBundle\Service\Request\Guzzle\GuzzleService as WebScrapperGuzzleService;

/**
 * Handles providing companies data from the {@link https://www.dnb.com/}
 */
class DunAndBradstreetService implements CompanyDataInterface
{
    /**
     * {@inheritDoc}
     */
    public function getServiceName(): string
    {
        return "DunAndBradstreet";
    }

    /**
     * Dnb has some weird protection that only certain user agents can be used for fetching company detail page
     * any agent other than these defined in here causes "403", no matter if it's the official once or some weird one
     * found over the internet
     */
    private const SUPPORTED_ORGANIZATION_DETAIL_PAGE_USER_AGENTS = [
        UserAgentConstants::FIREFOX_24,
        UserAgentConstants::CHROME_43,
    ];

    /**
     * Will return selector used to obtain company short description
     *
     * @return string
     */
    private function getCompanyShortDescriptionCssSelector(): string
    {
        return '[name^="company_description"] span';
    }

    /**
     * Will return selector used to obtain the company website
     *
     * @return string
     */
    private function getCompanyWebsiteCssSelector(): string
    {
        return '.company_profile_overview_underline_links #hero-company-link';
    }

    /**
     * Will return css selector used for extracting the company founded date
     *
     * @return string
     */
    private function getCompanyFoundedDateCssSelector(): string
    {
        return '[name^="year_started"]';
    }

    /**
     * Will return selector used for extracting the company target industries (specializations)
     *
     * @return string
     */
    private function getTargetIndustryCssSelector(): string
    {
        return '[name^="industry_links"] span a, [name^="industry_links"] span span span';
    }

    /**
     * Will return selector used for extracting the employees string
     *
     * @return string
     */
    private function getEmployeesNumberCssSelector(): string
    {
        return '[name^="employees_this_site"] span, [name^="employees_all_site"] span';
    }

    public function __construct(
        private LoggerInterface                   $logger,
        private CompanyInformationParser          $companyInformationParser,
        private RawCurlCrawlerEngineService       $rawCurlCrawlerEngineService,
        private readonly WebScrapperGuzzleService $webScrapperGuzzleService
    ) {
    }

    /**
     * Will return company data from the Crunchbase page
     *
     * @param string      $companyName
     * @param string|null $targetCountry3DigitIsoCode
     *
     * @return OrganizationDataDto|null
     *
     * @throws GuzzleException
     */
    public function getDataForCompanyName(string $companyName, ?string $targetCountry3DigitIsoCode = null): ?OrganizationDataDto
    {
        $this->logger->info("Trying to obtain data from dnb for company: {$companyName}");

        $organizationUrl = $this->getOrganizationDetailPageLink($companyName);
        if (empty($organizationUrl)) {
            return null;
        }

        /**
         * It's unknown why but the {@see RawCurlCrawlerEngineService} is the ONLY ONE that works with organization page
         * The loop over agents was added to handle case when Dnd blocks one of the user agents:
         * - with this there will be fallback to other user agents,
         * - information will be sent that Dnd is doing something to prevent crawling,
         */
        $crawlerConfiguration = new CrawlerConfigurationDto($organizationUrl, CrawlerService::CRAWLER_ENGINE_RAW_CURL);
        $crawler              = null;
        foreach(self::SUPPORTED_ORGANIZATION_DETAIL_PAGE_USER_AGENTS as $userAgent){
            try {
                $crawlerConfiguration->setUserAgent($userAgent);
                $crawler = $this->rawCurlCrawlerEngineService->crawl($crawlerConfiguration);

                break;
            } catch (Exception $e) {
                $message = "There is something wrong with calling organization detail page on `www.dnb.com`."
                    . " Might be that one of found working user agents is no longer working, trying next one";

                $this->logger->critical($message, [
                    "failedUserAgent" => $userAgent,
                    "organizationUrl" => $organizationUrl,
                    "exception"       => [
                        "message" => $e->getMessage(),
                        "code"    => $e->getCode(),
                    ]
                ]);
                continue;
            }
        }

        if (empty($crawler)) {
            $this->logger->critical("Could not crawl organization detail page on `www.dnb.com`", [
                "allUserAgents" => self::SUPPORTED_ORGANIZATION_DETAIL_PAGE_USER_AGENTS,
            ]);
            return null;
        }

        $foundedDate        = $this->obtainFoundedYear($crawler);
        $companyDescription = $this->obtainCompanyDescription($crawler);
        $targetIndustries   = $this->obtainTargetIndustries($crawler);
        $employeesNumber    = $this->obtainEmployeesNumber($crawler);
        $website            = $this->obtainWebsite($crawler);

        $host = (!empty($website) ? UrlHandlerService::getHost($website) : null);

        $organizationData = new OrganizationDataDto();
        $organizationData->setFoundedYear($foundedDate);
        $organizationData->setEmployeesNumber($employeesNumber);
        $organizationData->setTargetIndustries($targetIndustries);
        $organizationData->setCompanyDescription($companyDescription);
        $organizationData->setWebsiteMainPage($host);

        return $organizationData;
    }

    /**
     * Will try to find the organization detail page
     *
     * @param string $companyName
     *
     * @return string|null
     * @throws GuzzleException
     */
    private function getOrganizationDetailPageLink(string $companyName): ?string
    {
        $encodedCompanyName = urlencode($companyName);
        $host               = 'https://www.dnb.com';
        $baseUri            = $host . "/apps/dnb/servlets/CompanySearchServlet?pageNumber=1&pageSize=25&resourcePath=%2Fcontent%2Fdnb-us%2Fen%2Fhome%2Fsite-search-results%2Fjcr:content%2Fcontent-ipar-cta%2Fsinglepagesearch&returnNav=true&searchTerm=";
        $calledUrl          = $baseUri . $encodedCompanyName;

        try {
            /**
             * There is some weird thing happening with this service as it won't some user agents connect at all
             * - seems like only the {@see CrawlerEngineServiceInterface::USER_AGENT_INSOMNIA} works for now
             */

            $this->webScrapperGuzzleService->setIsWithProxy(EnvReader::isProxyEnabled());
            $this->webScrapperGuzzleService->setHeaders([
                GuzzleInterface::HEADER_HOST       => "www.dnb.com",
                GuzzleInterface::HEADER_USER_AGENT => UserAgentConstants::CHROME_85,
                GuzzleInterface::HEADER_ACCEPT     => "*/*",
            ]);

            $requestResult = $this->webScrapperGuzzleService->get($calledUrl, [
                RequestOptions::TIMEOUT         => 10,
                RequestOptions::CONNECT_TIMEOUT => 10,
            ]);

            $bodyContent = $requestResult->getBody()->getContents();
            $resultArray = json_decode($bodyContent, true);
            if (JSON_ERROR_NONE !== json_last_error()) {
                throw new Exception("Returned result is not a valid json: {$bodyContent}");
            }

            $companies = $resultArray['companies'] ?? null;
            if (empty($companies)) {
                $this->logger->warning("There are no results for given string: {$companyName}");
                return null;
            }

            foreach ($companies as $company) {
                $primaryCompanyName = $company['primaryName']        ?? null;
                $detailPageUri      = $company['companyProfileLink'] ?? null;
                $detailPageUrl      = $host . $detailPageUri;

                if (empty($detailPageUri)) {
                    $this->logger->critical("Dnb data is missing for", [
                        "companyName"   => $companyName,
                        "detailPageUri" => $detailPageUri,
                    ]);
                }

                if( $this->companyInformationParser->containsCompanyName($primaryCompanyName, $companyName) ){
                    return $detailPageUrl;
                }

            }
        } catch (Exception|TypeError $e) {

            /**
             * Dnb just works this way that it calls the url via ajax and if it throws 500 then does some other js things
             * So 500 here is just kinda "404"
             */
            if ($e->getCode() >= Response::HTTP_INTERNAL_SERVER_ERROR) {
                return null;
            }

            $this->logger->critical("Exception was thrown while trying to obtain search result page information from Dnb", [
                "exception" => [
                    "message" => $e->getMessage(),
                    "trace"   => $e->getTraceAsString(),
                ]
            ]);
            return null;
        }

        return null;
    }

    /**
     * Will return company founded date
     *
     * @param Crawler $crawler
     *
     * @return int|null
     */
    private function obtainFoundedYear(Crawler $crawler): ?int
    {
        $filteredNodes = $crawler->filter($this->getCompanyFoundedDateCssSelector());
        if (0 === $filteredNodes->count()) {
            return null;
        }

        $nodeTextContent = $filteredNodes->getNode(0)->textContent;
        preg_match("#(?<YEAR>([0-9]{4}))#", $nodeTextContent, $matches);
        $year = $matches["YEAR"] ?? null;
        return $year;
    }

    /**
     * Will return company short description
     *
     * @param Crawler $crawler
     *
     * @return string
     */
    private function obtainCompanyDescription(Crawler $crawler): string
    {
        $filteredNodes = $crawler->filter($this->getCompanyShortDescriptionCssSelector());
        if (0 === $filteredNodes->count()) {
            return "";
        }

        return $filteredNodes->getNode(0)->textContent;
    }

    /**
     * Will return name of industries in which company is specialized
     *
     * @param Crawler $crawler
     *
     * @return string[]
     */
    private function obtainTargetIndustries(Crawler $crawler): array
    {
        $targetIndustries = [];
        $filteredNodes    = $crawler->filter($this->getTargetIndustryCssSelector());
        if (0 === $filteredNodes->count()) {
            return [];
        }

        foreach ($filteredNodes as $node) {
            $targetIndustries[] = trim($node->textContent);
        }

        return $targetIndustries;
    }

    /**
     * Will return string containing information about amount of employees in company
     *
     * @param Crawler $crawler
     *
     * @return string|null
     */
    private function obtainEmployeesNumber(Crawler $crawler): ?string
    {
        $filteredNodes = $crawler->filter($this->getEmployeesNumberCssSelector());
        if (0 === $filteredNodes->count()) {
            return "";
        }

        $nodeTextContent = trim($filteredNodes->getNode(0)->textContent);
        preg_match("#(?<EMPLOYEES_COUNT>([0-9]*))#", $nodeTextContent, $matches);
        $employeesCount = $matches["EMPLOYEES_COUNT"] ?? null;

        return $employeesCount;
    }

    /**
     * Will return company website
     *
     * @param Crawler $crawler
     *
     * @return string|null
     */
    private function obtainWebsite(Crawler $crawler): ?string
    {
        $filteredNodes = $crawler->filter($this->getCompanyWebsiteCssSelector());
        if (0 === $filteredNodes->count()) {
            return null;
        }

        /** @var DomAttr $attribute */
        foreach ($filteredNodes->getNode(0)->attributes as $attribute) {
            if ($attribute->nodeName === "href") {
                return $attribute->value;
            }
        }

        return null;
    }

}
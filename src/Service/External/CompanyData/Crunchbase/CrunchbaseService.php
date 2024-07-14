<?php

namespace CompanyDataProvider\Service\External\CompanyData\Crunchbase;

use CompanyDataProvider\DTO\CompanyDataService\OrganizationDataDto;
use CompanyDataProvider\Service\External\CompanyData\CompanyDataInterface;
use CompanyDataProvider\Service\Parser\CompanyInformationParser;
use CompanyDataProvider\Service\Provider\Email\EmailProviderService;
use CompanyDataProvider\Service\System\EnvReader;
use DataParser\Service\Parser\Email\EmailParser;
use DOMAttr;
use SearchEngineProvider\Dto\SearchEngine\SearchEngineResultDto;
use SearchEngineProvider\Service\Paid\Search\PaidEngineSearchInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;
use SearchEngineProvider\Exception\SearchService\EngineNotSupported;
use SearchEngineProvider\Exception\SearchService\NoUsedEngineDefinedException;
use SearchEngineProvider\Service\SearchService;
use SmtpEmailValidatorBundle\Service\SmtpValidatorInterface;
use Symfony\Component\DomCrawler\Crawler;
use WebScrapperBundle\DTO\CrawlerConfigurationDto;
use WebScrapperBundle\Service\Analyse\ProtectedWebsiteAnalyser;
use WebScrapperBundle\Service\CrawlerService;
use WebScrapperBundle\Service\ScrapEngine\HeadlessChromeScrapEngine;

/**
 * Handles providing companies data from the {@link https://www.crunchbase.com/}
 *
 * Using the: {@see HeadlessChromeScrapEngine} because otherwise the requests get often banned (403)
 * due to detection of "automated tool", pretending to be a browser solves this issue
 */
class CrunchbaseService implements CompanyDataInterface
{
    /**
     * {@inheritDoc}
     */
    public function getServiceName(): string
    {
        return "Crunchbase";
    }

    /**
     * Will return css selector used to obtain the company detail block from detail page
     *
     * @return string
     */
    private function getCompanyDetailCssSelector(): string
    {
        return "#overview_default_view + section-card .section-content";
    }

    /**
     * Will return selector used to obtain company short description
     *
     * @return string
     */
    private function getCompanyShortDescriptionCssSelector(): string
    {
        return ".description";
    }

    /**
     * Will return css selector used for extracting the company founded date
     * - has to be extracted from the content of {@see CrunchbaseService::getDetailContentWithHtml()}
     *
     * @return string
     */
    private function getCompanyFoundedDateCssSelector(): string
    {
        return ".field-type-date_precision";
    }

    /**
     * Will return selector used for extracting the company target industries (specializations)
     * - has to be extracted from the content of {@see CrunchbaseService::getDetailContentWithHtml()}
     *
     * @return string
     */
    private function getTargetIndustryCssSelector(): string
    {
        return ".cb-overflow-ellipsis";
    }

    /**
     * Will return selector used for extracting the employees string
     *
     * @return string
     */
    private function getEmployeesNumberCssSelector(): string
    {
        return '.field-type-enum[href^="/search/people"]';
    }

    /**
     * Will return selector for extracting the linkedin url
     *
     * @return string
     */
    private function getLinkedinUrlCssSelector(): string
    {
        return ".section-content-wrapper [href*='linkedin.com']";
    }

    /**
     * Will return selector for extracting the facebook url
     *
     * @return string
     */
    private function getFacebookUrlCssSelector(): string
    {
        return ".section-content-wrapper [href*='facebook.com']";
    }

    /**
     * Will return selector for extracting the twitter url
     *
     * @return string
     */
    private function getTwitterUrlCssSelector(): string
    {
        return ".section-content-wrapper [href*='twitter.com']";
    }

    public function __construct(
        private SmtpValidatorInterface             $smtpValidator,
        private LoggerInterface                    $logger,
        private EmailProviderService               $emailProviderService,
        private readonly CrawlerService            $crawlerService,
        private CompanyInformationParser           $companyInformationParser,
        private readonly SearchService             $searchService,
        private readonly PaidEngineSearchInterface $paidEngineSearch
    ) {
        $this->paidEngineSearch->setAcceptUsage(true);
    }

    /**
     * Will return company data from the Crunchbase page
     *
     * @param string      $companyName
     * @param string|null $targetCountry3DigitIsoCode
     *
     * @return OrganizationDataDto|null
     *
     * @throws ContainerExceptionInterface
     * @throws EngineNotSupported
     * @throws NoUsedEngineDefinedException
     * @throws NotFoundExceptionInterface
     * @throws \Exception
     */
    public function getDataForCompanyName(string $companyName, ?string $targetCountry3DigitIsoCode = null): ?OrganizationDataDto
    {
        $this->logger->info("Trying to obtain data from Crunchbase for company: {$companyName}");

        $organizationUrl = $this->findCrunchbaseOrganizationLink($companyName, $targetCountry3DigitIsoCode);
        if (empty($organizationUrl)) {
            return null;
        }

        $crawlerConfig = $this->buildCrawlerConfig($organizationUrl);
        $crawler       = $this->crawlerService->crawl($crawlerConfig);
        $pageContent   = trim($crawler->html());

        $crawler                = new Crawler($pageContent);
        $detailBlockContentHtml = $this->getDetailContentWithHtml($crawler);

        $emailAddresses     = $this->obtainEmails($detailBlockContentHtml, $companyName);
        $foundedDate        = $this->obtainFoundedYear($detailBlockContentHtml);
        $companyDescription = $this->obtainCompanyDescription($crawler);
        $targetIndustries   = $this->obtainTargetIndustries($detailBlockContentHtml);
        $employeesNumber    = $this->obtainEmployeesNumber($crawler);
        $twitterUrl         = $this->obtainUrlForLinkSelector($crawler, $this->getTwitterUrlCssSelector());
        $facebookUrl        = $this->obtainUrlForLinkSelector($crawler, $this->getFacebookUrlCssSelector());
        $linkedinUrl        = $this->obtainUrlForLinkSelector($crawler, $this->getLinkedinUrlCssSelector());

        $organizationData = new OrganizationDataDto();
        $organizationData->setEmails($emailAddresses);
        $organizationData->setFoundedYear($foundedDate);
        $organizationData->setEmployeesNumber($employeesNumber);
        $organizationData->setTargetIndustries($targetIndustries);
        $organizationData->setCompanyDescription($companyDescription);
        $organizationData->setLinkedinUrl($linkedinUrl);
        $organizationData->setFacebookUrl($facebookUrl);
        $organizationData->setTwitterUrl($twitterUrl);

        return $organizationData;
    }

    /**
     * Will try to find Crunchbase organization detail page url,
     * this is way much efficient than searching for the organization on crunchbase itself
     * - this way it's higher probability rate of finding correct company on crunchbase
     *
     * @param string      $companyName
     * @param string|null $targetCountry3DigitIsoCode
     *
     * @return string|null
     *
     * @throws ContainerExceptionInterface
     * @throws EngineNotSupported
     * @throws NoUsedEngineDefinedException
     * @throws NotFoundExceptionInterface
     */
    private function findCrunchbaseOrganizationLink(string $companyName, ?string $targetCountry3DigitIsoCode = null): ?string
    {
        $searchedString = "crunchbase {$companyName}";
        $searchResults  = [];
        $link           = [];

        while (
            $this->searchService->hasNextEngine()
            && (is_null($link) || empty($searchResults))
        ) {
            $this->searchService->setNextEngine();

            $searchResults  = $this->searchService->getFirstPageSearchResultLinks($searchedString);
            $link           = $this->getLinkFromSearchResults($searchResults, $companyName);
        }

        if (empty($link)) {
            $paidResults = $this->paidEngineSearch->getSearchResults($searchedString, $targetCountry3DigitIsoCode);
            $link        = $this->getLinkFromSearchResults($paidResults, $companyName);
        }

        $this->searchService->resetEngines();

        return $link;
    }

    /***
     * @param SearchEngineResultDto[] $searchResults
     * @param string|null             $targetCountry3DigitIsoCode
     *
     * @return string|null
     */
    private function getLinkFromSearchResults(array $searchResults, ?string $targetCountry3DigitIsoCode = null): ?string
    {
        $link = null;
        foreach ($searchResults as $searchResult) {
            $normalizedDescription = strip_tags($searchResult->getDescription());
            if (
                    str_contains($searchResult->getLink(), "crunchbase")
                &&  $this->companyInformationParser->containsCompanyName($normalizedDescription, $targetCountry3DigitIsoCode)
            ) {
                $link = $searchResult->getLink();
                break;
            }
        }

        return $link;
    }

    /**
     * Will try to obtain emails from detail block content
     *
     * @param string $detailBlockContent
     * @param string $companyName
     *
     * @return string[]
     */
    private function obtainEmails(string $detailBlockContent, string $companyName): array
    {
        $filteredContent = strip_tags($detailBlockContent);
        $emailAddresses  = EmailParser::parseEmailsFromString($filteredContent);
        if (empty($emailAddresses)) {
            return [];
        }

        $matchingEmails = [];
        foreach ($emailAddresses as $emailAddress) {
            $validationResults = $this->smtpValidator->validateEmail([$emailAddress]);
            if (!in_array($emailAddress, $validationResults)) {
                $this->logger->warning("E-mail: {$emailAddress} could not be validated");
                continue;
            }

            $isRealEmail = $validationResults[$emailAddress];
            if (!$isRealEmail) {
                $this->logger->warning("Found E-Mail ({$emailAddress}), but SMTP validation returned false - can mean that E-Mail is invalid");
                continue;
            }

            $filteredEmails = $this->emailProviderService->filterEmails([$emailAddress], $companyName);
            if (empty($filteredEmails)) {
                continue;
            }

            $matchingEmails = array_merge($matchingEmails, $filteredEmails);
        }

        if (!empty($matchingEmails)) {
            $this->logger->info("Found E-Mail(s) suitable for job applications");
        }

        return $matchingEmails;
    }

    /**
     * Will return content of the company details block (as html)
     *
     * @param Crawler $crawler
     *
     * @return string
     */
    private function getDetailContentWithHtml(Crawler $crawler): string
    {
        $filteredNodes = $crawler->filter($this->getCompanyDetailCssSelector());
        if (0 === $filteredNodes->count()) {
            return "";
        }

        $subCrawler         = new Crawler($filteredNodes->getNode(0));
        $detailBlockContent = $subCrawler->html();
        return $detailBlockContent;
    }

    /**
     * Will return company founded date
     *
     * @param string $detailBlockContentHtml
     *
     * @return int|null
     */
    private function obtainFoundedYear(string $detailBlockContentHtml): ?int
    {
        $crawler       = new Crawler($detailBlockContentHtml);
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
     * @param string $detailBlockContentHtml
     *
     * @return string[]
     */
    private function obtainTargetIndustries(string $detailBlockContentHtml): array
    {
        $targetIndustries = [];
        $crawler       = new Crawler($detailBlockContentHtml);
        $filteredNodes = $crawler->filter($this->getTargetIndustryCssSelector());
        if (0 === $filteredNodes->count()) {
            return [];
        }

        foreach ($filteredNodes as $node) {
            $targetIndustries[] = $node->textContent;
        }

        return $targetIndustries;
    }

    /**
     * Will return string containing information about amount of employees in company
     *
     * @param Crawler $crawler
     *
     * @return string
     */
    private function obtainEmployeesNumber(Crawler $crawler): string
    {
        $filteredNodes = $crawler->filter($this->getEmployeesNumberCssSelector());
        if (0 === $filteredNodes->count()) {
            return "";
        }

        return $filteredNodes->getNode(0)->textContent;
    }

    /**
     * Will return the url in href attribute of <A> tag
     *
     * @param Crawler $crawler
     * @param string  $selector
     *
     * @return string|null
     */
    private function obtainUrlForLinkSelector(Crawler $crawler, string $selector): ?string
    {
        $filteredNodes = $crawler->filter($selector);
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

    /**
     * Creates the crawler configuration, dev uses different one that prod.
     * Prod is not allowed to use {@see HeadlessChromeScrapEngine} because it
     * will produce high fees for using unlocker-proxy.
     *
     * {@see HeadlessChromeScrapEngine} can be used on dev, and kinda must, because it's the only way to make the results be
     * scrapped without unlocker-proxy.
     *
     * No proxy configuration is getting set here because this is handled via {@see ProtectedWebsiteAnalyser} which
     * will automatically apply the unlocker proxy on prod.
     *
     * @param string $organizationUrl
     *
     * @return CrawlerConfigurationDto
     */
    private function buildCrawlerConfig(string $organizationUrl): CrawlerConfigurationDto
    {
        if (EnvReader::isDev()) {
            return new CrawlerConfigurationDto($organizationUrl, CrawlerService::SCRAP_ENGINE_HEADLESS);
        }

        return new CrawlerConfigurationDto($organizationUrl, CrawlerService::CRAWLER_ENGINE_GOUTTE);
    }

}
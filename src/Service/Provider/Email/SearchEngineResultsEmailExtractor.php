<?php

namespace CompanyDataProvider\Service\Provider\Email;

use CompanyDataProvider\DTO\Provider\Email\CompanyEmailsDto;
use CompanyDataProvider\Service\Decider\CompanyEmailDecider;
use CompanyDataProvider\Service\Url\UrlHandlerService;
use Psr\Cache\CacheException;
use Psr\Cache\InvalidArgumentException;
use SearchEngineProvider\Dto\SearchEngine\SearchEngineResultDto;
use SearchEngineProvider\Service\SearchEngine\SimpleDomContent\EngineServiceInterface;

/**
 * Handles extracting emails from {@see EngineServiceInterface} results
 */
class SearchEngineResultsEmailExtractor
{
    /**
     * Links that were already crawled, prevent from crawling pages over again
     *
     * @var array
     */
    private array $crawledLinks = [];

    public function __construct(
        private readonly LinkEmailExtractor $linkEmailExtractor
    ) {
    }

    /**
     * Will attempt to obtain the E-Mail from {@see SearchEngineResultDto}
     *
     * @param SearchEngineResultDto[] $resultsDto
     * @param CompanyEmailsDto        $companyEmailsDto
     * @param string                  $companyName
     *
     * @return CompanyEmailsDto
     * @throws CacheException
     * @throws InvalidArgumentException
     */
    public function handle(array $resultsDto, CompanyEmailsDto $companyEmailsDto, string $companyName): CompanyEmailsDto
    {
        $this->crawledLinks = [];

        $this->tryForMatchingLinks($companyEmailsDto, $companyName, $resultsDto);

        /**
         * Sometimes companies got totally different domains than company name itself,
         * that's why if there were no E-Mails found the first results will be checked if it's related to the company
         * name somehow, BUT ONLY the 2 first one as usually any other is not related to it, just mentions the company
         * name in title OR in description
         */
        if (!$companyEmailsDto->hasEmails()) {
            $this->tryFromPaginationFirstResults($companyEmailsDto, $resultsDto, $companyName);
        }

        return $companyEmailsDto;
    }

    /**
     * @param CompanyEmailsDto        $companyEmailsDto
     * @param string                  $companyName
     * @param SearchEngineResultDto[] $resultsDto
     *
     * @return void
     * @throws CacheException
     * @throws InvalidArgumentException
     */
    private function tryForMatchingLinks(CompanyEmailsDto $companyEmailsDto, string $companyName, array $resultsDto): void
    {
        if (empty($resultsDto)) {
            return;
        }

        $usedWebsiteUrl  = null;
        $emails          = [];
        $foundUrls       = $this->decideUsedSearchEngineUrls($resultsDto);

        // contains the E-Mails found on the earlier link (got higher chance of being valid)
        $earliestFoundEmails = [];
        $earliestWebsiteUrl  = null;

        foreach ($foundUrls as $websiteLink) {
            $emails = [];

            if (
                    in_array($websiteLink, $this->crawledLinks)
                || !filter_var($websiteLink, FILTER_VALIDATE_URL)
            ) {
                $this->crawledLinks[] = $websiteLink;
                continue;
            }

            $this->crawledLinks[] = $websiteLink;
            $emails               = $this->linkEmailExtractor->get($websiteLink, $companyName);
            if (is_null($emails)) {
                $emails = [];
                continue;
            }

            // that's correct since job application E-Mails are the preferred one, so if none was found then look further
            $jobApplicationEmails = CompanyEmailDecider::filterJobApplicationEmails($emails);
            if (empty($jobApplicationEmails)) {
                continue;
            }

            $usedWebsiteUrl = $websiteLink;
            break;
        }

        if (empty($emails)) {
            $emails         = $earliestFoundEmails;
            $usedWebsiteUrl = $earliestWebsiteUrl;
        }

        $companyEmailsDto->setEmails($emails);
        $companyEmailsDto->setUsedWebsiteUrl($usedWebsiteUrl);
    }

    /**
     * @param CompanyEmailsDto        $companyEmailsDto
     * @param SearchEngineResultDto[] $resultsDto
     * @param string                  $companyName
     *
     * @throws CacheException
     * @throws InvalidArgumentException
     */
    private function tryFromPaginationFirstResults(CompanyEmailsDto $companyEmailsDto, array $resultsDto, string $companyName): void
    {
        if (empty($resultsDto)) {
            return;
        }

        $maxChecked     = 2;
        $checkedCount   = 0;
        foreach ($resultsDto as $resultDto) {
            if ($checkedCount >= $maxChecked) {
                return;
            }

            if (
                    str_contains($resultDto->getDescription(), $companyName)
                &&  !in_array($resultDto->getLink(), $this->crawledLinks)
                &&  filter_var($resultDto->getLink(), FILTER_VALIDATE_URL)
            ) {
                $emails         = $this->linkEmailExtractor->get($resultDto->getLink(), $companyName, false);
                $usedWebsiteUrl = $resultDto->getLink();

                if (!empty($emails)) {
                    $companyEmailsDto->setUsedWebsiteUrl($usedWebsiteUrl);
                    $companyEmailsDto->setEmails($emails);

                    return;
                }
            }

            $checkedCount++;
        }

    }

    /**
     * Will decide which urls should be used for crawling,
     * thing is that in some cases the engine returns links with and without the http(s),
     * and the crawler from (@see CrawlerService) won't work without the link consisting of the protocol
     *
     * So the goal of this method is to go over the search results and if there are 2 the same uris but with and without
     * the protocol, then the one with protocol is taken
     *
     * It's required to use the {@see UrlHandlerService::appendHttp()} because otherwise the {@see parse_url()}
     * will not be able to extract the host/uri
     * - keep in mind that not using the {@see UrlHandlerService::appendHttp()} for obtaining SCHEME
     *   is made on purpose, because if it's used then both url without scheme and the one that has the scheme
     *   to begin with will be marked as "HAS SCHEME", and no replacement will be made
     *
     * @param SearchEngineResultDto[] $resultsDto
     *
     * @return array
     */
    private function decideUsedSearchEngineUrls(array $resultsDto): array
    {
        $usedUrls = [];
        foreach ($resultsDto as $resultDto) {
            if (empty($usedUrls)) {
                $usedUrls[] = $resultDto->getLink();
                continue;
            }

            $normalizedDtoLink = UrlHandlerService::appendHttp($resultDto->getLink());
            $resultHost        = parse_url($normalizedDtoLink, PHP_URL_HOST);
            $resultUri         = parse_url($normalizedDtoLink, PHP_URL_QUERY);
            $resultScheme      = parse_url($resultDto->getLink(), PHP_URL_SCHEME);

            foreach ($usedUrls as $index => $usedUrl) {

                $normalizedUrl = UrlHandlerService::appendHttp($usedUrl);
                $usedUrlUri    = parse_url($normalizedUrl, PHP_URL_QUERY);
                $usedUrlHost   = parse_url($normalizedUrl, PHP_URL_HOST);
                $usedUrlScheme = parse_url($usedUrl, PHP_URL_SCHEME);
                if (
                        $usedUrlUri === $resultUri
                    &&  $usedUrlHost === $resultHost
                    &&  empty($usedUrlScheme)
                    &&  !empty($resultScheme)
                ) {
                    $usedUrls[$index] = $resultDto->getLink();
                    continue 2;
                }
            }

            $usedUrls[] = $resultDto->getLink();
        }

        return $usedUrls;
    }

}
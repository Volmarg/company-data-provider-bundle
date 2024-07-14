<?php

namespace CompanyDataProvider\Service\Provider\Email;

use CompanyDataProvider\Service\Constant\MessageConstants;
use CompanyDataProvider\Service\DataBus\WebsiteContentDataBus;
use CompanyDataProvider\Service\Decider\CompanyEmailDecider;
use CompanyDataProvider\Service\Parser\CompanyInformationParser;
use CompanyDataProvider\Service\System\EnvReader;
use CompanyDataProvider\Service\Url\UrlHandlerService;
use DataParser\Service\Parser\Email\EmailParser;
use Exception;
use Psr\Cache\CacheException;
use Psr\Cache\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use WebScrapperBundle\DTO\CrawlerConfigurationDto;
use WebScrapperBundle\Service\CrawlerService;

/**
 * Handles extracting emails from target link
 */
class LinkEmailExtractor
{
    public function __construct(
        private readonly WebsiteContentDataBus    $websiteContentDataBus,
        private readonly CompanyInformationParser $companyInformationParser,
        private readonly LoggerInterface          $logger,
        private readonly CrawlerService           $crawlerService,
        private readonly CompanyEmailDecider      $companyEmailDecider
    ) {

    }

    /**
     * Will attempt to extract email from provided website (url)
     *
     * @param string $url
     * @param string $companyName
     * @param bool   $validateDomain     - decide if the domain relation to company name should be checked
     * @param bool   $skipAlreadyCrawled - helps skipping already crawled websites (by checking if such website is already in local cache)
     *
     * @return array|null     - array if search was made, null if no search was performed as for example url host is not related to company name
     * @throws CacheException
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function get(string $url, string $companyName, bool $validateDomain = true, bool $skipAlreadyCrawled = true): ?array
    {
        $host = UrlHandlerService::getHost($url);

        // obtained data is not a valid link - inaccurate selector, or same selector is shared for multiple elements
        if (empty($host)) {
            return [];
        }

        $cachedWebsiteDto = $this->websiteContentDataBus->retrieve($host);

        /**
         * info: this might cause some issues in future if the cached website will be first added from other place than this service
         *
         * This also might help in cases where few ppl make similar search and one search already did check for "X" domain
         */
        $skipCrawling = (
                !empty($cachedWebsiteDto?->hasExactUrl($url))
            &&  $skipAlreadyCrawled
        );

        // checking domain over link, yields better comparison accuracy
        if (
            (
                    $this->isDomainRelatedToCompany($url, $companyName)
                ||  !$validateDomain
            )
            && !$skipCrawling
        ) {
            $usedUrl = UrlHandlerService::appendHttp($url);
            $this->logger->info("Crawling link: " . $usedUrl);

            // proxy is used in here in order to hide the identity while crawling pages
            $crawlerConfigurationDto = new CrawlerConfigurationDto($usedUrl, CrawlerService::CRAWLER_ENGINE_GOUTTE);
            $crawlerConfigurationDto->setWithProxy(EnvReader::isProxyEnabled());

            try {
                $crawler = $this->crawlerService->crawl($crawlerConfigurationDto);
                $html    = $crawler->html(); // need to call the method in order to throw some exceptions that can be ignored

                $this->websiteContentDataBus->store($url, $html);
            } catch (Exception $e) {
                if ($this->isIgnoredCrawlingException($e)) {
                    return null;
                }
                throw $e;
            }
        }

        if (empty($html) && $cachedWebsiteDto?->hasExactUrl($url)) {
            $html = $cachedWebsiteDto->getContentForUrl($url);
        }

        if (empty($html)) {
            return null;
        }

        $emails = EmailParser::parseEmailsFromString($html) ?? [];
        $emails = $this->companyEmailDecider->filterRelatedToCompany($emails, $companyName);

        return $emails;
    }


    /**
     * Check if given crawling exception should be ignored or not as it's not depending on the code implementation etc.
     * but rather on the page crawling issues (timeout, host not reachable etc.)
     *
     * @param Exception $e
     *
     * @return bool
     */
    private function isIgnoredCrawlingException(Exception $e): bool
    {
        return  str_contains($e->getMessage(), MessageConstants::COULD_NOT_RESOLVE_HOST)      // happens when either host is indeed unreachable or provided url is wrong (rarely)
            ||  str_contains($e->getMessage(), MessageConstants::CONNECTION_TIMED_OUT)        // related to guzzle `timeout`
            ||  str_contains($e->getMessage(), MessageConstants::CURRENT_NODE_LIST_IS_EMPTY); // can happen for example if crawled url has disposition header (file download)
    }

    /**
     * @param string $url
     * @param string $companyName
     *
     * @return bool
     */
    private function isDomainRelatedToCompany(string $url, string $companyName): bool
    {
        $domain = UrlHandlerService::getDomainOnly($url);

        return $this->companyInformationParser->isRelatedToCompanyName($domain ?? '', $companyName);
    }

}
<?php

namespace CompanyDataProvider\Service\Provider;

use Exception;
use Psr\Log\LoggerInterface;
use WebScrapperBundle\DTO\CrawlerConfigurationDto;
use WebScrapperBundle\Service\CrawlerService;

/**
 * Will attempt providing variety of data from the page content using regexp
 */
class PageSourceRegexpDataProvider
{
    public function __construct(
        private readonly CrawlerService $crawlerService,
        private LoggerInterface         $logger,
    ){}

    /**
     * Will attempt to extract data from page source using regexp,
     *
     * @param string      $pageContent
     * @param string      $regexp
     * @param string|null $returnedGroupName - if null is provided then [0] is returned, meaning whole match
     *                                       - info: groupName means grouping used in regex, example (?<NAME>PATTERN)
     *
     * @return string|int|null - null means that no match was found
     */
    public function get(string $pageContent, string $regexp, ?string $returnedGroupName = null): string | int | null
    {
        preg_match($regexp, $pageContent, $matches);
        if (empty($returnedGroupName)) {
            return $matches[0] ?? null;
        }

        $groupValue = $matches[$returnedGroupName] ?? null;

        return $groupValue;
    }

    /**
     * Will attempt to extract the page content, if null is returned then something went wrong
     *
     * @param string $url
     *
     * @return string|null
     */
    public function getPageContent(string $url): ?string
    {
        try {
            $crawlerConfiguration = new CrawlerConfigurationDto($url, CrawlerService::CRAWLER_ENGINE_GOUTTE);
            $crawler              = $this->crawlerService->crawl($crawlerConfiguration);

            return $crawler->html();
        } catch (Exception $e) {
            $this->logger->warning("Could not get page content of url: {$url}", [
                "exception" => [
                    "message" => $e->getMessage(),
                    "trace"   => $e->getTraceAsString(),
                ]
            ]);
            return null;
        }
    }
}

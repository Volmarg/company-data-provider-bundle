<?php

namespace CompanyDataProvider\Service\External\CompanyRating;

use CompanyDataProvider\DTO\Rating\RatingProviderDto;
use CompanyDataProvider\Exception\RatingProviderException;
use CompanyDataProvider\Service\System\EnvReader;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;
use TypeError;
use WebScrapperBundle\Service\Request\Guzzle\GuzzleInterface;
use WebScrapperBundle\Service\Request\Guzzle\GuzzleService as WebScrapperGuzzleService;

/**
 * Based on the {@link https://www.kununu.com/}
 * Covers countries:
 * - GERMANY
 */
class KununRatingProvider implements RatingProviderInterface
{
    private const COMPANY_NAME_MIN_SIMILARITY_PERCENT = 70;

    private const RATING_MIN = 1;
    private const RATING_MAX = 5;

    public function __construct(
        private LoggerInterface                   $logger,
        private readonly WebScrapperGuzzleService $webScrapperGuzzleService
    ){}

    /**
     * {@inheritDoc}
     */
    public function getRating(string $companyName, ?string $locationName = null): ?RatingProviderDto
    {
        $matchingCompanies = $this->getMatchingCompanies($companyName, $locationName);
        if (empty($matchingCompanies)) {
            return null;
        }

        $matchingCompany = $this->decideMostMatchingResult($matchingCompanies, $companyName);
        $permaLink       = $this->getPermalink($matchingCompany);
        if (empty($permaLink)) {
            $companyDataJson = json_encode($matchingCompany);
            $this->logger->critical("Could not extract permalink from company data: {$companyDataJson}");
            return null;
        }

        $ratingWebsite = $this->buildRatingPageUrl($permaLink);
        $currentRating = $this->getCurrentRating($matchingCompany);
        if (is_null($currentRating)) {
            $companyDataJson = json_encode($matchingCompany);
            $this->logger->critical("Could not extract rating from company data: {$companyDataJson}");
            return null;
        }

        $ratingDto = new RatingProviderDto(
            $companyName,
            self::RATING_MIN,
            self::RATING_MAX,
            $currentRating,
            $ratingWebsite
        );

        return $ratingDto;
    }

    /**
     * Will return array of data of companies which are matching the input criteria
     *
     * @throws GuzzleException
     * @throws RatingProviderException
     */
    private function getMatchingCompanies(string $companyName, ?string $locationName = null): array
    {
        try {
            $calledUrl = $this->buildUrlToGetMatchingCompanies($companyName, $locationName);

            $this->webScrapperGuzzleService->setIsWithProxy(EnvReader::isProxyEnabled());
            $this->webScrapperGuzzleService->setHeaders([
                GuzzleInterface::KEY_HEADERS => "application/vnd.kununu.v1+json;version=2016-05-11"
            ]);

            $response  = $this->webScrapperGuzzleService->get($calledUrl);

            $content   = $response->getBody()->getContents();
            $dataArray = json_decode($content, true);
            if (JSON_ERROR_NONE !== json_last_error()) {
                $message = "Matching companies data is not valid json! Json error: " . json_last_error_msg()
                           . ". Called url: " . $calledUrl;
                $this->logger->critical($message);
                return [];
            }

            $matchingCompanies = $dataArray['profiles'] ?? [];
            if (empty($matchingCompanies)) {
                $message = "No data was returned at all for the companies, probably the `profiles` key is not set in response array";
                $this->logger->critical($message);
                return [];
            }

        } catch (Exception|TypeError $e) {
            throw new RatingProviderException(self::class, $e->getMessage(), $e->getTraceAsString());
        }

        return $matchingCompanies;
    }

    /**
     * Will return url used for getting the matching companies
     *
     * @param string      $companyName
     * @param string|null $locationName
     *
     * @return string
     */
    private function buildUrlToGetMatchingCompanies(string $companyName, ?string $locationName): string
    {
        $normalizedCompanyName  = str_replace(" ","+", $companyName);
        $url                    = "https://api.kununu.com/v1/search/profiles?page=1&q={$normalizedCompanyName}&per_page=18";

        if (!empty($locationName)) {
            $normalizedLocationName = str_replace(" ","+", $locationName);
            $url                   .= "&location={$normalizedLocationName}";
        }

        return $url;
    }

    /**
     * Will return rating page detail uri from the data that is getting delivered from the kunun api
     *
     * @param array $companyDataArray
     *
     * @return string|null
     */
    private function getPermalink(array $companyDataArray): ?string
    {
        $permaLink = $companyDataArray["permalink"] ?? null;
        return $permaLink;
    }

    /**
     * Will return current rating of company - extracted from kunun api response
     *
     * @param array $companyDataArray
     *
     * @return string|null
     */
    private function getCurrentRating(array $companyDataArray): ?string
    {
        $currentRating = $companyDataArray["total"] ?? null;
        return $currentRating;
    }

    /**
     * Will return company name - extracted from kunun api response
     *
     * @param array $companyDataArray
     *
     * @return string|null
     */
    private function getCompanyName(array $companyDataArray): ?string
    {
        $currentRating = $companyDataArray["name"] ?? null;
        return $currentRating;
    }

    /**
     * Will return the url of the page on which all the rating information can be found
     *
     * @param string $permaLink
     *
     * @return string
     */
    private function buildRatingPageUrl(string $permaLink): string
    {
        return "https://www.kununu.com/{$permaLink}";
    }

    /**
     * Will decide which of the results should be used to retrieve company rating,
     * This is needed because few matching results are being returned for searched strings,
     * but some of them are totally not related to company
     *
     * @param array  $matchingCompanies
     * @param string $companyName
     *
     * @return array
     */
    private function decideMostMatchingResult(array $matchingCompanies, string $companyName): array
    {
        $lastSimilarityPercent = 0;
        $mostMatchingResult    = [];
        foreach ($matchingCompanies as $matchingCompany) {
            $resultCompanyName = $this->getCompanyName($matchingCompany);

            similar_text($companyName, $resultCompanyName, $similarityPercentage);
            if ($similarityPercentage < self::COMPANY_NAME_MIN_SIMILARITY_PERCENT) {
                continue;
            }

            if ($similarityPercentage > $lastSimilarityPercent) {
                $lastSimilarityPercent = $similarityPercentage;

                $mostMatchingResult = $matchingCompany;
            }
        }

        return $mostMatchingResult;
    }

}
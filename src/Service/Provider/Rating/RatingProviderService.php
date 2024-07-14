<?php

namespace CompanyDataProvider\Service\Provider\Rating;

use CompanyDataProvider\DTO\Rating\RatingProviderDto;
use CompanyDataProvider\Enum\CountryCode\Iso3166CountryCodeEnum;
use CompanyDataProvider\Service\External\CompanyRating\KununRatingProvider;
use CompanyDataProvider\Service\External\CompanyRating\RatingProviderInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Provides rating for country
 */
class RatingProviderService
{
    public function __construct(
        private readonly KernelInterface $kernel,
        private readonly LoggerInterface $logger,
    )
    {}

    /**
     * @return string[]
     */
    public function mapIso3166threeDigitCountryCodeToProvider(): array
    {
        return [
            Iso3166CountryCodeEnum::GERMANY_3_DIGIT->value => KununRatingProvider::class
        ];
    }

    /**
     * Returns {@see RatingProviderDto} if rating service exists for country code and rating was found,
     * else null is returned
     *
     * @param string      $countryCode
     * @param string      $companyName
     * @param string|null $locationName
     *
     * @return RatingProviderDto|null
     */
    public function getRating(string $countryCode, string $companyName, ?string $locationName = null): ?RatingProviderDto
    {
        $countryCodeToProvider = $this->mapIso3166threeDigitCountryCodeToProvider();
        $fqnForCountryCode     = $countryCodeToProvider[$countryCode] ?? null;

        if (empty($fqnForCountryCode)) {
            $this->logger->warning("No rating service is defined for country code: {$countryCode}");
            return null;
        }

        /** @var RatingProviderInterface $provider */
        $provider = $this->kernel->getContainer()->get($fqnForCountryCode);
        $rating   = $provider->getRating($companyName, $locationName);

        return $rating;
    }
}
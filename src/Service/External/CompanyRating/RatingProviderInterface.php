<?php

namespace CompanyDataProvider\Service\External\CompanyRating;

use CompanyDataProvider\DTO\Rating\RatingProviderDto;

/**
 * Describes common logic for all the rating providers
 */
interface RatingProviderInterface
{
    /**
     * Will provide rating for company, null is returned if no matching was found
     *
     * @param string      $companyName
     * @param string|null $locationName
     *
     * @return RatingProviderDto|null
     */
    public function getRating(string $companyName, ?string $locationName = null): ?RatingProviderDto;
}
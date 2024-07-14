<?php

namespace CompanyDataProvider\DTO\Rating;

use CompanyDataProvider\Service\External\CompanyRating\RatingProviderInterface;

/**
 * Contains set of data returned by {@see RatingProviderInterface}
 */
class RatingProviderDto
{
    public function __construct(
        private string  $companyName,
        private float   $ratingMin,
        private float   $ratingMax,
        private float   $ratingNow,
        private string  $ratingWebsite,
        private ?string $locationName = null,
    ){}

    /**
     * @return string
     */
    public function getCompanyName(): string
    {
        return $this->companyName;
    }

    /**
     * @param string $companyName
     */
    public function setCompanyName(string $companyName): void
    {
        $this->companyName = $companyName;
    }

    /**
     * @return float
     */
    public function getRatingMin(): float
    {
        return $this->ratingMin;
    }

    /**
     * @param float $ratingMin
     */
    public function setRatingMin(float $ratingMin): void
    {
        $this->ratingMin = $ratingMin;
    }

    /**
     * @return float
     */
    public function getRatingMax(): float
    {
        return $this->ratingMax;
    }

    /**
     * @param float $ratingMax
     */
    public function setRatingMax(float $ratingMax): void
    {
        $this->ratingMax = $ratingMax;
    }

    /**
     * @return float
     */
    public function getRatingNow(): float
    {
        return $this->ratingNow;
    }

    /**
     * @param float $ratingNow
     */
    public function setRatingNow(float $ratingNow): void
    {
        $this->ratingNow = $ratingNow;
    }

    /**
     * @return string
     */
    public function getRatingWebsite(): string
    {
        return $this->ratingWebsite;
    }

    /**
     * @param string $ratingWebsite
     */
    public function setRatingWebsite(string $ratingWebsite): void
    {
        $this->ratingWebsite = $ratingWebsite;
    }

    /**
     * @return string|null
     */
    public function getLocationName(): ?string
    {
        return $this->locationName;
    }

    /**
     * @param string|null $locationName
     */
    public function setLocationName(?string $locationName): void
    {
        $this->locationName = $locationName;
    }

}
<?php

namespace CompanyDataProvider\DTO\Crunchbase;

/**
 * Represents result returned for example from:
 * - {@link https://www.crunchbase.com/v4/data/autocompletes?query=sunrise%20system&collection_ids=organizations&limit=1&source=topSearch}
 */
class SearchResultDto
{
    public function __construct(
        private ?string $logoUrl = null,
        private ?string $detailPageUrl = null,
        private ?string $companyShortDescription = null
    )
    {}

    /**
     * @return string|null
     */
    public function getLogoUrl(): ?string
    {
        return $this->logoUrl;
    }

    /**
     * @param string|null $logoUrl
     */
    public function setLogoUrl(?string $logoUrl): void
    {
        $this->logoUrl = $logoUrl;
    }

    /**
     * @return string|null
     */
    public function getDetailPageUrl(): ?string
    {
        return $this->detailPageUrl;
    }

    /**
     * @return bool
     */
    public function hasDetailPageUrl(): bool
    {
       return !empty($this->getDetailPageUrl());
    }

    /**
     * @param string|null $detailPageUrl
     */
    public function setDetailPageUrl(?string $detailPageUrl): void
    {
        $this->detailPageUrl = $detailPageUrl;
    }

    /**
     * @return string|null
     */
    public function getCompanyShortDescription(): ?string
    {
        return $this->companyShortDescription;
    }

    /**
     * @param string|null $companyShortDescription
     */
    public function setCompanyShortDescription(?string $companyShortDescription): void
    {
        $this->companyShortDescription = $companyShortDescription;
    }

}
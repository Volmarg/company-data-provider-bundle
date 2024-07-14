<?php

namespace CompanyDataProvider\DTO\CompanyDataService;

/**
 * Represents the data from for example:
 * - {@link https://www.crunchbase.com/organization/biedronka}
 * - {@link https://www.dnb.com/business-directory/company-profiles.endresshauser_wetzer_gmbhco_kg.10a7d71b8806838a422e29fad92e3adb.html}
 */
class OrganizationDataDto
{
    public function __construct(
        private array  $emails = [],
        private ?int   $foundedYear = null,
        private string $companyDescription = "",
        private array  $targetIndustries = [],
        private ?string $employeesNumber = "",
        private ?string $facebookUrl = null,
        private ?string $twitterUrl = null,
        private ?string $linkedinUrl = null,
        private ?string $websiteMainPage = null
    ) {}

    /**
     * @return array
     */
    public function getEmails(): array
    {
        return $this->emails;
    }

    /**
     * @param array $emails
     */
    public function setEmails(array $emails): void
    {
        $this->emails = $emails;
    }

    /**
     * @return string
     */
    public function getCompanyDescription(): string
    {
        return $this->companyDescription;
    }

    /**
     * @param string $companyDescription
     */
    public function setCompanyDescription(string $companyDescription): void
    {
        $this->companyDescription = $companyDescription;
    }

    /**
     * @return array
     */
    public function getTargetIndustries(): array
    {
        return $this->targetIndustries;
    }

    /**
     * @param array $targetIndustries
     */
    public function setTargetIndustries(array $targetIndustries): void
    {
        $this->targetIndustries = $targetIndustries;
    }

    /**
     * @return int|null
     */
    public function getFoundedYear(): ?int
    {
        return $this->foundedYear;
    }

    /**
     * @param int|null $foundedYear
     */
    public function setFoundedYear(?int $foundedYear): void
    {
        $this->foundedYear = $foundedYear;
    }

    /**
     * @return string|null
     */
    public function getFacebookUrl(): ?string
    {
        return $this->facebookUrl;
    }

    /**
     * @param string|null $facebookUrl
     */
    public function setFacebookUrl(?string $facebookUrl): void
    {
        $this->facebookUrl = $facebookUrl;
    }

    /**
     * @return string|null
     */
    public function getTwitterUrl(): ?string
    {
        return $this->twitterUrl;
    }

    /**
     * @param string|null $twitterUrl
     */
    public function setTwitterUrl(?string $twitterUrl): void
    {
        $this->twitterUrl = $twitterUrl;
    }

    /**
     * @return string|null
     */
    public function getLinkedinUrl(): ?string
    {
        return $this->linkedinUrl;
    }

    /**
     * @param string|null $linkedinUrl
     */
    public function setLinkedinUrl(?string $linkedinUrl): void
    {
        $this->linkedinUrl = $linkedinUrl;
    }

    /**
     * @return string|null
     */
    public function getEmployeesNumber(): ?string
    {
        return $this->employeesNumber;
    }

    /**
     * @param string|null $employeesNumber
     */
    public function setEmployeesNumber(?string $employeesNumber): void
    {
        $this->employeesNumber = $employeesNumber;
    }

    /**
     * @return string|null
     */
    public function getWebsiteMainPage(): ?string
    {
        return $this->websiteMainPage;
    }

    /**
     * @param string|null $websiteMainPage
     */
    public function setWebsiteMainPage(?string $websiteMainPage): void
    {
        $this->websiteMainPage = $websiteMainPage;
    }

}
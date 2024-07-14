<?php

namespace CompanyDataProvider\DTO;

use CompanyDataProvider\Enum\DTO\CompanyData\JobApplicationEmailSourceEnum;
use CompanyDataProvider\Service\Url\UrlHandlerService;

/**
 * Stores the company data
 */
class CompanyDataDto
{
    public function __construct(
        private string                         $companyName = "",
        private array                          $emails = [],
        private array                          $jobApplicationEmails = [],
        private ?string                        $website = null,
        private ?JobApplicationEmailSourceEnum $jobApplicationEmailSource = null,
        private ?int                           $foundedYear = null,
        private string                         $companyDescription = "",
        private array                          $targetIndustries = [],
        private string                         $employeesNumber = "",
        private ?string                        $facebookUrl = null,
        private ?string                        $twitterUrl = null,
        private ?string                        $linkedinUrl = null,
    ) {}

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
     * @return string|null
     */
    public function getWebsite(): ?string
    {
        return $this->website;
    }

    /**
     * @param string|null $website
     */
    public function setWebsite(?string $website): void
    {
        $this->website = $website;
    }

    /**
     * Will check if website is present
     *
     * @return bool
     */
    public function hasWebsite(): bool
    {
        return !empty($this->website);
    }

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
     * Will check if there are any E-Mails at all
     *
     * @return bool
     */
    public function hasEmails(): bool
    {
       return !empty($this->emails);
    }

    /**
     * @return array
     */
    public function getJobApplicationEmails(): array
    {
        return $this->jobApplicationEmails;
    }

    /**
     * @param array $jobApplicationEmails
     */
    public function setJobApplicationEmails(array $jobApplicationEmails): void
    {
        $this->jobApplicationEmails = $jobApplicationEmails;
    }

    /**
     * Will return count of job application E-Mails
     *
     * @return int
     */
    public function getJobApplicationEmailsCount(): int
    {
        return count($this->getJobApplicationEmails());
    }

    /**
     * Will check if there are any job application E-Mails at all
     *
     * @return bool
     */
    public function hasJobApplicationEmails(): bool
    {
        return !empty($this->jobApplicationEmails);
    }

    /**
     * @param string $email
     */
    public function addJobApplicationEmail(string $email): void
    {
        if (!in_array($email, $this->jobApplicationEmails)) {
            $this->jobApplicationEmails[] = $email;
         }
    }

    /**
     * @return JobApplicationEmailSourceEnum|null
     */
    public function getJobApplicationEmailSource(): ?JobApplicationEmailSourceEnum
    {
        return $this->jobApplicationEmailSource;
    }

    /**
     * @param JobApplicationEmailSourceEnum|null $jobApplicationEmailSource
     */
    public function setJobApplicationEmailSource(?JobApplicationEmailSourceEnum $jobApplicationEmailSource): void
    {
        $this->jobApplicationEmailSource = $jobApplicationEmailSource;
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
     * @return string
     */
    public function getEmployeesNumber(): string
    {
        return $this->employeesNumber;
    }

    /**
     * @param string $employeesNumber
     */
    public function setEmployeesNumber(string $employeesNumber): void
    {
        $this->employeesNumber = $employeesNumber;
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
    public function getWebsiteHost(): ?string
    {
        if (empty($this->getWebsite())) {
            return null;
        }

        return UrlHandlerService::getHost($this->getWebsite());
    }

}
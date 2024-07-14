<?php

namespace CompanyDataProvider\DTO\Provider\Email;

use CompanyDataProvider\Enum\DTO\CompanyData\JobApplicationEmailSourceEnum;
use CompanyDataProvider\Service\Provider\Email\EmailProviderService;
use CompanyDataProvider\Service\Url\UrlHandlerService;

/**
 * Represents data set returned from {@see EmailProviderService}
 */
class CompanyEmailsDto
{
    /**
     * @var array $emails
     */
    private array $emails = [];

    /**
     * @var array $jobApplicationEmails
     */
    private array $jobApplicationEmails;

    /**
     * @var JobApplicationEmailSourceEnum $jobApplicationEmailSource
     */
    private JobApplicationEmailSourceEnum $jobApplicationEmailSource;

    /**
     * Website on which the E-Mails were found
     *
     * @var string|null $usedWebsiteUrl
     */
    private ?string $usedWebsiteUrl;

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
        $filteredEmails = [];
        foreach ($emails as $email) {
            $normalisedEmail = trim($email);
            if (empty($normalisedEmail)) {
                continue;
            }

            $filteredEmails[] = $normalisedEmail;
        }

        $this->emails = $filteredEmails;
    }

    /**
     * @return int
     */
    public function countEmails(): int
    {
        return count($this->getEmails());
    }

    /**
     * @return bool
     */
    public function hasEmails(): bool
    {
        return !empty($this->getEmails());
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
     * @return string|null
     */
    public function getUsedWebsiteUrl(): ?string
    {
        return $this->usedWebsiteUrl;
    }

    /**
     * @param string|null $usedWebsiteUrl
     */
    public function setUsedWebsiteUrl(?string $usedWebsiteUrl): void
    {
        $this->usedWebsiteUrl = $usedWebsiteUrl;
    }

    /**
     * @return string|null
     */
    public function getUsedWebsiteHost(): ?string
    {
        if (empty($this->usedWebsiteUrl)) {
            return null;
        }

        return UrlHandlerService::getHost($this->usedWebsiteUrl);
    }

    /**
     * @return JobApplicationEmailSourceEnum
     */
    public function getJobApplicationEmailSource(): JobApplicationEmailSourceEnum
    {
        return $this->jobApplicationEmailSource;
    }

    /**
     * @param JobApplicationEmailSourceEnum $jobApplicationEmailSource
     */
    public function setJobApplicationEmailSource(JobApplicationEmailSourceEnum $jobApplicationEmailSource): void
    {
        $this->jobApplicationEmailSource = $jobApplicationEmailSource;
    }

    /**
     * @return int
     */
    public function hasAnyEmails(): int
    {
        return (count($this->getEmails()) !== 0);
    }

}
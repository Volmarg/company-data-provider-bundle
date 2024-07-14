<?php

namespace CompanyDataProvider\Service\Decider;

use CompanyDataProvider\Service\Parser\CompanyInformationParser;
use CompanyDataProvider\Service\Provider\Email\EmailProviderInterface;

/**
 * Decides if the email is proper for given company
 */
class CompanyEmailDecider
{
    public function __construct(
        private readonly CompanyInformationParser $companyInformationParser
    ) {
    }

    /**
     * Decide if the E-Mail is valid
     *
     * @param string $email
     * @param string $companyName
     *
     * @return bool
     */
    public function isValid(string $email, string $companyName): bool
    {
        if ($this->isRelatedToCompanyName($email, $companyName)) {
            return true;
        }

        return false;
    }

    /**
     * Will check if found E-Mails are anyhow related to the company name,
     * This is needed because it may happen that incorrect url is used to fetch E-Mails data from,
     * or some other source provided some totally wrong E-Mail addresses
     *
     * @param Array<string> $emails
     * @param string        $companyName
     *
     * @return array
     */
    public function filterRelatedToCompany(array $emails, string $companyName): array
    {
        $companyValidEmails = [];
        foreach ($emails as $email) {
            if ($this->isValid($email, $companyName)) {
                $companyValidEmails[] = $email;
            }
        }

        $companyValidEmails = array_unique($companyValidEmails);

        return $companyValidEmails;
    }

    /**
     * Will return E-Mails which can be used as application E-Mails:
     * Imagine such case:
     * - contact@qossmic.com
     * - privacy@qossmic.com
     *
     * In that example, only the `contact` is the E-Mail that can be used for job application
     *
     * @param array $emails
     * @return array
     */
    public static function filterJobApplicationEmails(array $emails): array
    {
        $filteredEmails = [];
        foreach ($emails as $email) {
            $emailPartials    = explode("@", $email);
            $emailAccountName = array_shift($emailPartials);

            $isExcluded = false;
            foreach (self::getJobApplicationEmailExcludeStrings() as $string) {
                if (stristr($emailAccountName, $string)) {
                    $isExcluded = true;
                    break;
                }
            }

            if (!$isExcluded) {
                $filteredEmails[] = $email;
            }

        }

        $filteredEmails = array_unique($filteredEmails);

        return $filteredEmails;
    }

    /**
     * Check if either the domain or recipient (string before "@") are related to company name,
     *
     * @param string $email
     * @param string $companyName
     *
     * @return bool
     */
    private function isRelatedToCompanyName(string $email, string $companyName): bool
    {
        $emailPartials = explode("@", $email);
        $domain        = array_pop($emailPartials);
        $recipient     = array_pop($emailPartials);

        $domainPartials = explode(".", $domain);
        $domainName     = array_shift($domainPartials);

        return (
                $this->companyInformationParser->isRelatedToCompanyName($domainName, $companyName)
            ||  $this->companyInformationParser->isRelatedToCompanyName($recipient, $companyName)
        );
    }

    /**
     * Will basically return the {@see EmailProviderInterface::JOB_APPLICATION_EMAIL_EXCLUDE_STRINGS} but
     * without the grouping present
     *
     * @return array
     */
    private static function getJobApplicationEmailExcludeStrings(): array
    {
        $strings = [];
        foreach (EmailProviderInterface::JOB_APPLICATION_EMAIL_EXCLUDE_STRINGS as $stringsGroup) {
            foreach ($stringsGroup as $string) {
                $strings[] = $string;
            }
        }

        return $strings;
    }
}
<?php

namespace CompanyDataProvider\Service\Provider\Email;

use CompanyDataProvider\DTO\Provider\Email\CompanyEmailsDto;
use CompanyDataProvider\Enum\DTO\CompanyData\JobApplicationEmailSourceEnum;
use CompanyDataProvider\Exception\Provider\ProviderException;
use CompanyDataProvider\Service\Decider\CompanyEmailDecider;
use CompanyDataProvider\Service\External\CompanyData\Crunchbase\CrunchbaseService;
use CompanyDataProvider\Service\External\SocialMedia\LinkedinCompanyFinderService;
use CompanyDataProvider\Service\Parser\CompanyLocationParser;
use CompanyDataProvider\Service\Provider\Email\SearchEngineStrings\AppendedStringConstants;
use CompanyDataProvider\Service\Provider\Email\SearchEngineStrings\AppendedStringProvider;
use Exception;
use Psr\Cache\CacheException;
use Psr\Cache\InvalidArgumentException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;
use SearchEngineProvider\Service\Paid\Search\PaidEngineSearchInterface;
use SearchEngineProvider\Service\SearchEngine\Other\DuckDuck\DuckDuckEngineService;
use SearchEngineProvider\Service\SearchEngine\SimpleDomContent\BingEngineService;
use SearchEngineProvider\Service\SearchEngine\SimpleDomContent\DuckDuckHtmlEngineService;
use SearchEngineProvider\Service\SearchEngine\SimpleDomContent\YahooEngineService;
use SearchEngineProvider\Service\SearchService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use TypeError;

/**
 * Handles providing E-Mail for company
 *
 * [What have already been tried but failed]
 * - Spam bombing SMPT servers with list of possible accounts that could potentially exist and be used as job-application-emails
 *   - this has failed as in most cases (~95% of them) it ended up with "blocked" / "no such user" / "connection issues"
 *
 */
class EmailProviderService extends AbstractController implements EmailProviderInterface
{
    /**
     * @var string[] $searchEngineExcludedFiletypes
     */
    private array $searchEngineExcludedFiletypes = [
        "pdf",
    ];

    /**
     * @throws Exception
     */
    public function __construct(
        private LoggerInterface                    $logger,
        private SearchService                      $searchService,
        private readonly PaidEngineSearchInterface $paidEngineSearch,
        private readonly CompanyEmailDecider       $companyEmailDecider,
        private readonly SearchEngineResultsEmailExtractor $searchEngineResultsEmailExtractor
    ){
        $this->setSearchEngineSettings();
        $this->paidEngineSearch->setAcceptUsage(true);
    }

    /**
     * This sets settings used by search service inside this class, that's a must because the:
     * - {@see PaidEngineSearchInterface} sets some settings to the {@see SearchService} on its own
     * and then these are also getting used in here due to DI
     *
     * @throws Exception
     */
    private function setSearchEngineSettings(): void
    {
        $this->searchService->setUsedEnginesFqns([
            DuckDuckEngineService::class,
            BingEngineService::class,
            DuckDuckHtmlEngineService::class,
            YahooEngineService::class,
        ]);

        $this->searchService->setExcludedFileTypes($this->searchEngineExcludedFiletypes);
        $this->searchService->setWithProxy(false);
        $this->searchService->setProxyCountryIsoCode(null);
        $this->searchService->setProxyUsage(null);
        $this->searchService->setForceAllowEngineFqns([]);
        $this->searchService->setUsedProxyIdentifier(null);
    }

    /**
     * Will attempt to find company website uri on which an E-Mail can be found,
     * Relies on {@see EngineServiceInterface} as it's easier to find page with E-Mail this way
     *
     * @param string      $companyName
     * @param string|null $companyLocation
     * @param string|null $threeDigitIsoCode - this is for example used to determine which localized strings
     *                                         should be appended when searching for emails via {@see SearchService}
     *                                         - see {@see AppendedStringConstants}
     *
     * @return CompanyEmailsDto|null
     * @throws CacheException
     * @throws ContainerExceptionInterface
     * @throws InvalidArgumentException
     * @throws NotFoundExceptionInterface
     * @throws ProviderException
     */
    public function getFromWebsite(string $companyName, ?string $companyLocation, ?string $threeDigitIsoCode = null): ?CompanyEmailsDto
    {
        try{
            $this->logger->info("Trying to obtain E-Mails from website");
            $normalisedLocation = CompanyLocationParser::clear($companyLocation);

            /**
             * Searching "string by string" is more accurate, provides E-Mails for more companies,
             * tried already using the {@see EngineServiceInterface::getOrOperator()} but yielded result
             * are worse.
             *
             * So current solution is around 50% slower than using {@see EngineServiceInterface::getOrOperator()}
             */
            $companyEmailsDto = $this->searchStringByString($companyName, $normalisedLocation, $threeDigitIsoCode);
            if (!$companyEmailsDto->hasAnyEmails()) {
                $companyEmailsDto = $this->searchWithPaidService($companyName, $threeDigitIsoCode);
                $this->setSearchEngineSettings();
            }

            $jobApplicationEmails = CompanyEmailDecider::filterJobApplicationEmails($companyEmailsDto->getEmails());
            $companyEmailsDto->setJobApplicationEmails($jobApplicationEmails);

            if (!empty($jobApplicationEmails)) {
                $jobApplicationEmailCount = count($companyEmailsDto->getJobApplicationEmails());
                $this->logger->info("Got: {$jobApplicationEmailCount} E-Mail(s) suitable for job applications");

                $companyEmailsDto->setJobApplicationEmailSource(JobApplicationEmailSourceEnum::WEBSITE);
                return $companyEmailsDto;
            }

                $this->logger->info("None of the E-Mails can be used for job application.", [
                    "emails" => $companyEmailsDto->getEmails()
                ]);
        } catch (Exception|TypeError $e) {
            throw new ProviderException($e);
        }

        return null;
    }

    /**
     * It will call given functions one after another
     * - {@see CompanyEmailDecider::filterRelatedToCompany()}
     * - {@see CompanyEmailDecider::filterJobApplicationEmails()}
     *
     * @param Array<string> $emails
     * @param string        $companyName
     *
     * @return array
     */
    public function filterEmails(array $emails, string $companyName): array
    {
        $filteredEmails = CompanyEmailDecider::filterJobApplicationEmails($emails);
        $filteredEmails = $this->companyEmailDecider->filterRelatedToCompany($filteredEmails, $companyName);

        return $filteredEmails;
    }

    /**
     * Will basically return the {@see EmailProviderInterface::JOB_APPLICATION_EMAIL_PREFERRED_SUBSTRINGS} but
     * without the grouping present
     *
     * @return string[]
     */
    public static function getJobApplicationEmailPreferredSubstrings(): array
    {
        $strings = [];
        foreach (EmailProviderInterface::JOB_APPLICATION_EMAIL_PREFERRED_SUBSTRINGS as $stringsGroup) {
            foreach ($stringsGroup as $string) {
                $strings[] = $string;
            }
        }

        return $strings;
    }

    /**
     * Will search for the E-Mail using {@see SearchService} but will call the logic for every
     * string separately, so if there are 5 patterns which can be used to obtain the E-Mail then each engine will
     * be called 5 times (each string one time)
     *
     * {@see self::setSearchEngineSettings} is called here as otherwise it causes issue which allows next engine
     * to be Google called via paid service, so it would generate a lot of costs. This was happening when first
     * {@see CrunchbaseService} or {@see LinkedinCompanyFinderService} would be called.
     *
     * @param string      $companyName
     * @param string|null $companyLocation
     * @param string|null $threeDigitIsoCode - this is for example used to determine which localized strings
     *                                         should be appended when searching for emails via {@see SearchService}
     *                                         - see {@see AppendedStringConstants}
     *
     * @return CompanyEmailsDto
     * @throws CacheException
     * @throws ContainerExceptionInterface
     * @throws InvalidArgumentException
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    private function searchStringByString(string $companyName, ?string $companyLocation, ?string $threeDigitIsoCode = null): CompanyEmailsDto
    {
        $this->setSearchEngineSettings();

        $resultDtos       = [];
        $companyEmailsDto = new CompanyEmailsDto();
        $appendedStrings  = AppendedStringProvider::getAppendedStrings($threeDigitIsoCode);
        foreach ($appendedStrings as $appendedString) {
            if ($companyEmailsDto->hasEmails()) {
                $this->logger->info("Found E-Mails - not searching any more");
                break;
            }

            $searchedString = $companyName . " " . $appendedString;
            if (!empty($companyLocation)) {
                $searchedString = $companyName . " " . $companyLocation . " " . $appendedString;
            }

            try {
                while (
                    $this->searchService->hasNextEngine()
                    && (!$companyEmailsDto->hasEmails() || empty($resultDtos))
                ) {
                    $this->searchService->setNextEngine();
                    $this->logger->info("Searching for website with string: '{$searchedString}' (engine: {$this->searchService->getCurrentlyUsedEngineClass()})");

                    $resultDtos       = $this->searchService->getFirstPageSearchResultLinks($searchedString);
                    $companyEmailsDto = $this->searchEngineResultsEmailExtractor->handle($resultDtos, $companyEmailsDto, $companyName);
                    if ($companyEmailsDto->hasEmails()) {
                        $this->logger->info("Found: {$companyEmailsDto->countEmails()} E-Mail/s. Not looking for more");
                        break;
                    }
                }

            } catch (Exception|TypeError $e) {
                $this->logger->warning("Failed getting data with engine for string {$searchedString}, trying next string", [
                    "exception" => [
                        "message" => $e->getMessage(),
                        "trace"   => $e->getTraceAsString(),
                    ],
                ]);
                continue;
            }

            $this->searchService->resetEngines();
        }

        $this->searchService->resetEngines();

        return $companyEmailsDto;
    }

    /**
     * @param string      $companyName
     * @param string|null $threeDigitIsoCode
     *
     * @return CompanyEmailsDto
     * @throws CacheException
     * @throws InvalidArgumentException
     */
    private function searchWithPaidService(string $companyName, ?string $threeDigitIsoCode = null): CompanyEmailsDto
    {
        $companyEmailsDto  = new CompanyEmailsDto();
        $searchedString    = $companyName . " " . AppendedStringConstants::GENERIC_STRING_EMAIL;
        $paidSearchResults = $this->paidEngineSearch->getSearchResults($searchedString, $threeDigitIsoCode);

        return $this->searchEngineResultsEmailExtractor->handle($paidSearchResults, $companyEmailsDto, $companyName);
    }

}
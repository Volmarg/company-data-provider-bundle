<?php

namespace CompanyDataProvider\Controller\Provider;

use CompanyDataProvider\DTO\CompanyDataDto;
use CompanyDataProvider\DTO\CompanyDataService\OrganizationDataDto;
use CompanyDataProvider\DTO\Provider\Email\CompanyEmailsDto;
use CompanyDataProvider\Enum\DTO\CompanyData\JobApplicationEmailSourceEnum;
use CompanyDataProvider\Exception\Provider\ProviderException;
use CompanyDataProvider\Service\AllowanceChecker\AllowanceCheckerInterface;
use CompanyDataProvider\Service\Constant\RegexpConstants;
use CompanyDataProvider\Service\DataBus\WebsiteContentDataBus;
use CompanyDataProvider\Service\External\CompanyData\CompanyDataInterface;
use CompanyDataProvider\Service\External\CompanyData\Crunchbase\CrunchbaseService;
use CompanyDataProvider\Service\External\CompanyData\DunAndBradstreet\DunAndBradstreetService;
use CompanyDataProvider\Service\External\SocialMedia\LinkedinCompanyFinderService;
use CompanyDataProvider\Service\Provider\Email\EmailProviderService;
use CompanyDataProvider\Service\Provider\PageSourceRegexpDataProvider;
use CompanyDataProvider\Service\TypeProcessor\ArrayTypeProcessor;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Cache\CacheException;
use Psr\Cache\InvalidArgumentException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;
use SearchEngineProvider\Exception\SearchService\EngineNotSupported;
use SearchEngineProvider\Exception\SearchService\NoUsedEngineDefinedException;
use TypeError;
use WebScrapperBundle\Exception\MissingDependencyException;

/**
 * Handles providing company data
 */
class CompanyDataProviderController implements CompanyDataProviderInterface
{
    /**
     * @var CompanyDataInterface[] $companyDataServices
     */
    private array $companyDataServices;

    public function __construct(
        private readonly EmailProviderService         $emailProviderService,
        private readonly CrunchbaseService            $crunchbaseService,
        private readonly DunAndBradstreetService      $dunAndBradstreetService,
        private readonly LinkedinCompanyFinderService $linkedinCompanyFinderService,
        private readonly WebsiteContentDataBus        $websiteContentDataBus,
        private readonly PageSourceRegexpDataProvider $pageSourceRegexpDataProvider,
        private readonly AllowanceCheckerInterface    $apiCallAllowanceChecker,
        private readonly LoggerInterface              $logger
    ) {
        /**
         * Order matters here since the {@see DunAndBradstreetService} is by far the fastest one,
         * any next service is treated as fallback
         */
        $this->companyDataServices = [
            // $dunAndBradstreetService,
            $crunchbaseService,
        ];

        $this->init();
    }

    /**
     * Handles providing company data for company name
     *
     * @param string      $companyName
     * @param string|null $companyLocation
     * @param string|null $threeDigitIsoCode   - this is for example used to determine which localized strings
     *                                         should be appended when searching for emails via {@see SearchService}
     *                                         - see {@see AppendedStringConstants}
     *
     * @return CompanyDataDto | null
     * @throws CacheException
     * @throws InvalidArgumentException
     * @throws ProviderException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws GuzzleException
     */
    public function getForCompany(string $companyName, ?string $companyLocation = null, ?string $threeDigitIsoCode = null): ?CompanyDataDto
    {
        $companyData = new CompanyDataDto();
        $companyData->setCompanyName($companyName);

        try {
            /**
             * This is not executed one after another, each function has check if the logic should be executed or not
             *
             * Keep in min that {@see EmailProviderService} is called first on purpose as it uses the {@see WebsiteContentDataBus}
             * for caching re-using page content, and it checks if page was already crawled or not,
             * so if some other service caches the data first it might then be incorrectly get skipped in {@see EmailProviderService::getFromWebsite()}
             * - cache is stored in {@see EmailProviderService::getFromSingleEngineResult}
             */
            $companyEmailsDto = $this->emailProviderService->getFromWebsite($companyName, $companyLocation, $threeDigitIsoCode);
            $organizationData = $this->getOrganizationData($companyName, $threeDigitIsoCode);
            if (!empty($organizationData)) {
                $this->fillCompanyFromOrganization($companyData, $organizationData);
            }

            $this->fillFromCompanyEmails($companyData, $companyEmailsDto);
            $this->fillCompanySocialMediaLinks($companyData, $organizationData);

        } catch (Exception|TypeError $e) {
            throw new ProviderException($e);
        }

        return $companyData;
    }

    /**
     * Initialise the company data provider services that can be used to obtain the data,
     * the decision if given api can be used is made by {@see AllowanceCheckerInterface::isAllowed()}
     */
    private function init(): void
    {
        foreach ($this->companyDataServices as $idx =>  $companyDataService) {
            $isAllowed = $this->apiCallAllowanceChecker->isAllowed($companyDataService->getServiceName());
            if (!$isAllowed) {
                $this->logger->warning("Company data provider service has been disabled", [
                    "disabledService"  => $companyDataService->getServiceName(),
                    "allowanceChecker" => $this->apiCallAllowanceChecker::class
                ]);

                unset($this->companyDataServices[$idx]);
            }
        }

        if (empty($this->companyDataServices)) {
            $this->logger->critical("No company data provider is active, thus no company data will be delivered!");
            return;
        }

        // reindex
        $this->companyDataServices = array_values($this->companyDataServices);
    }

    /**
     * Will return company data from external company data providing service
     * It doesn't work with the `location` so only company name can be provided, else it will return incorrect data.
     *
     * @param string      $companyName
     * @param string|null $targetCountry3DigitIsoCode
     *
     * @return OrganizationDataDto|null
     *
     * @throws ContainerExceptionInterface
     * @throws EngineNotSupported
     * @throws GuzzleException
     * @throws MissingDependencyException
     * @throws NoUsedEngineDefinedException
     * @throws NotFoundExceptionInterface
     */
    private function getOrganizationData(string $companyName, ?string $targetCountry3DigitIsoCode = null): ?OrganizationDataDto
    {
        foreach ($this->companyDataServices as $companyDataService) {
            $organizationData = $companyDataService->getDataForCompanyName($companyName, $targetCountry3DigitIsoCode);
            if (empty($organizationData)) {
                continue;
            }

            return $organizationData;
        }
        return null;
    }

    /**
     * Will try to fill the provided {@see CompanyDataDto} from {@see OrganizationDataDto}
     *
     * @param CompanyDataDto      $companyDataDto
     * @param OrganizationDataDto $organizationData
     *
     * @return CompanyDataDto
     * @throws Exception
     */
    private function fillCompanyFromOrganization(CompanyDataDto $companyDataDto, OrganizationDataDto $organizationData): CompanyDataDto
    {
        if (!empty($organizationData->getEmails())) {
            $allEmails = [
                ...$companyDataDto->getJobApplicationEmails(),
                ...$organizationData->getEmails(),
            ];

            $jobApplicationEmailSource = ($companyDataDto->hasJobApplicationEmails() ? JobApplicationEmailSourceEnum::MIXED : JobApplicationEmailSourceEnum::CRUNCHBASE);
            $companyDataDto->setJobApplicationEmails($allEmails);

            // info: not used now, there should be table 1:n on the job-searcher to map each email to source
            //  $companyDataDto->setJobApplicationEmailSource($jobApplicationEmailSource);
        }

        // checking if empty since the organization based website might be outdated, so should be kinda "last resort"
        if (empty($companyDataDto->getWebsite())) {
            $companyDataDto->setWebsite($organizationData->getWebsiteMainPage());
        }

        $companyDataDto->setTwitterUrl($organizationData->getTwitterUrl());
        $companyDataDto->setFacebookUrl($organizationData->getFacebookUrl());
        $companyDataDto->setTargetIndustries($organizationData->getTargetIndustries());
        $companyDataDto->setFoundedYear($organizationData->getFoundedYear());
        $companyDataDto->setCompanyDescription($organizationData->getCompanyDescription());
        $companyDataDto->setEmployeesNumber($organizationData->getEmployeesNumber());

        return $companyDataDto;
    }

    /**
     * Will attempt to fill the social media links, this has been separated from the {@see CompanyDataProviderController::fillCompanyFromOrganization()}
     * so that it can be better controlled where the links are going to be provided from.
     *
     * For now this only handles the linkedin url, but if it's needed to be expanded then it should be split into new methods
     *
     * @param CompanyDataDto           $companyDataDto
     * @param OrganizationDataDto|null $organizationData
     *
     * @return CompanyDataDto
     *
     * @throws ContainerExceptionInterface
     * @throws EngineNotSupported
     * @throws InvalidArgumentException
     * @throws NoUsedEngineDefinedException
     * @throws NotFoundExceptionInterface
     */
    private function fillCompanySocialMediaLinks(CompanyDataDto $companyDataDto, ?OrganizationDataDto $organizationData): CompanyDataDto
    {
        if (!empty($companyDataDto->getLinkedinUrl())) {
            return $companyDataDto;
        }

        $linkedinUrl = null;
        if (!empty($organizationData?->getLinkedinUrl())) {
            $linkedinUrl = $organizationData->getLinkedinUrl();
        }

        // obtain from website
        if (empty($linkedinUrl)) {
            $dto = null;
            if (!empty($companyDataDto->getWebsiteHost())) {
                $dto = $this->websiteContentDataBus->retrieve($companyDataDto->getWebsiteHost());
            }

            $websiteContent = null;
            if (!empty($dto)) {
                // using any url to the website because most of the websites have the social media urls all over the page in footer/header etc.
                $websiteWithContent = $dto->getFirstWebsiteContent();
                $websiteContent     = $websiteWithContent[array_key_first($websiteWithContent)];
            }

            if (
                    empty($websiteContent)
                &&  !empty($companyDataDto->getWebsite())
            ) {
                $websiteContent = $this->pageSourceRegexpDataProvider->getPageContent($companyDataDto->getWebsite());
            }

            if (!empty($websiteContent)) {
                $linkedinUrl = $this->pageSourceRegexpDataProvider->get(
                    $websiteContent,
                    "#" . RegexpConstants::LINKEDIN_COMPANY_URL_IN_TAG_ATTRIBUTE . "#m",
                    'LINKEDIN_URL'
                );
            }

        }

        // still empty - last fallback to finding url via search engine result
        if(empty($linkedinUrl)){
            $linkedinUrl = $this->linkedinCompanyFinderService->findUrlForCompany($companyDataDto->getCompanyName());
        }

        if (
                !is_null($linkedinUrl)
            &&  preg_match("#" . RegexpConstants::LINKEDIN_COMPANY_URL . "#", $linkedinUrl)
        ) {
            $companyDataDto->setLinkedinUrl($linkedinUrl);
        }

        return $companyDataDto;
    }

    /**
     * Will fill part of the company data with information fetched from {@see EmailProviderService}
     *
     * @param CompanyDataDto        $companyData
     * @param CompanyEmailsDto|null $companyEmailsDto
     */
    private function fillFromCompanyEmails(CompanyDataDto $companyData, ?CompanyEmailsDto $companyEmailsDto): void
    {
        if(empty($companyEmailsDto)){
            return;
        }

        if (empty($companyData->getWebsite())) {
            $companyData->setWebsite($companyEmailsDto->getUsedWebsiteHost());
        }

        $allEmails = ArrayTypeProcessor::array_iunique([
            ...$companyData->getEmails(),
            ...$companyEmailsDto->getEmails()
        ]);

        $allJobApplicationEmails = ArrayTypeProcessor::array_iunique([
            ...$companyEmailsDto->getJobApplicationEmails(),
            ...$companyData->getJobApplicationEmails(),
        ]);

        $companyData->setEmails($allEmails);
        $companyData->setJobApplicationEmails($allJobApplicationEmails);

        // info: not used now, there should be table 1:n on the job-searcher to map each email to source
        //  $companyData->setJobApplicationEmailSource($companyEmailsDto->getJobApplicationEmailSource());
    }

}
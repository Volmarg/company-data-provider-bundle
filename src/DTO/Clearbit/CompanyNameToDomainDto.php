<?php

namespace CompanyDataProvider\DTO\Clearbit;

/**
 * Represents structure of the clearbit api call for obtaining company domain from company name
 */
class CompanyNameToDomainDto
{

    /**
     * @var string $name
     */
    private string $name;

    /**
     * @var string $name
     */
    private string $domain;

    /**
     * @var string $name
     */
    private string $logo;

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getDomain(): string
    {
        return $this->domain;
    }

    /**
     * @param string $domain
     */
    public function setDomain(string $domain): void
    {
        $this->domain = $domain;
    }

    /**
     * @return string
     */
    public function getLogo(): string
    {
        return $this->logo;
    }

    /**
     * @param string $logo
     */
    public function setLogo(string $logo): void
    {
        $this->logo = $logo;
    }

}
<?php

namespace CompanyDataProvider\DTO\Cache;

/**
 * Represents cached website under given host
 */
class CachedWebsiteDto
{
    /**
     * @var array $websiteContents
     */
    private array $websiteContents = [];

    /**
     * @return string
     */
    public function getHost(): string
    {
        return $this->host;
    }

    public function __construct(
        private string $host
    ) {}

    /**
     * @return array - each key is a website, value is content of that website
     */
    public function getWebsiteContents(): array
    {
        return $this->websiteContents;
    }

    /**
     * @param array $websiteContents
     */
    public function setWebsiteContents(array $websiteContents): void
    {
        $this->websiteContents = $websiteContents;
    }

    /**
     * Add website to the list
     *
     * @param string $url
     * @param string $content
     *
     * @return void
     */
    public function addWebsite(string $url, string $content)
    {
        if (array_key_exists($url, $this->websiteContents)) {
            return;
        }

        $this->websiteContents[$url] = $content;
    }

    /**
     * @return array - key is the url, value is the content
     */
    public function getFirstWebsiteContent(): array
    {
        if (empty($this->websiteContents)) {
            return [];
        }

        $firstKey = array_key_first($this->websiteContents);
        $value    = $this->websiteContents[$firstKey];

        return [$firstKey => $value];
    }

    /**
     * Will check if given url is present in {@see CachedWebsiteDto::$websiteContents} array
     *
     * @param string $url
     *
     * @return bool
     */
    public function hasExactUrl(string $url): bool
    {
       return array_key_exists($url, $this->websiteContents);
    }

    /**
     * @param string $url
     *
     * @return string|null
     */
    public function getContentForUrl(string $url): ?string
    {
        return $this->websiteContents[$url];
    }

}
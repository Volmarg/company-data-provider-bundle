<?php

namespace CompanyDataProvider\Service\DataBus;

use CompanyDataProvider\DTO\Cache\CachedWebsiteDto;
use CompanyDataProvider\Service\Url\UrlHandlerService;
use LogicException;
use Psr\Cache\CacheException;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Website content caching mechanism,
 * handles saving page content using {@see AdapterInterface}
 */
class WebsiteContentDataBus
{

    /**
     * @var FilesystemAdapter $cache
     */
    private readonly FilesystemAdapter $cache;

    public function __construct(
        private readonly SerializerInterface $serializer,
        ParameterBagInterface                $parameterBag
    ){
        $this->cache = new FilesystemAdapter(
            $parameterBag->get("cache.website.namespace"),
            $parameterBag->get("cache.website.lifetime"),
            $parameterBag->get("cache.website.directory"),
        );
    }

    /**
     * Will store website data in cache,
     * - if given website is already stored then nothing happens,
     * - if no key for host exists yet then new one will be set, else old is used for update
     *
     * @param string $url
     * @param string $content
     *
     * @return void
     * @throws InvalidArgumentException
     * @throws CacheException
     */
    public function store(string $url, string $content): void
    {
        $host = $this->extractHost($url);
        $dto  = $this->retrieve($url);

        if (empty($dto)) {
            $dto = new CachedWebsiteDto($host);
        }

        $dto->addWebsite($url, $content);

        $serializedDto = $this->serializer->serialize($dto, "json");

        /**
         * According to the stack overflow it's the only sane way to set the cache key:
         * - @link https://stackoverflow.com/questions/70589454/how-to-set-the-key-for-a-cache-item-with-symfony-cache
         *
         * Other than that Symfony documentation does not mention how to do that some other proper way
         */
        $cacheItem = $this->cache->getItem($host);

        $cacheItem->set($serializedDto);
        $this->cache->save($cacheItem);
    }

    /**
     * Will return {@see CachedWebsiteDto} for host extracted from provided url
     * else returns null if no match is found - meaning no cache entry exists
     *
     * @param string $url
     *
     * @return CachedWebsiteDto|null
     * @throws InvalidArgumentException
     */
    public function retrieve(string $url): ?CachedWebsiteDto
    {
        $host = $this->extractHost($url);
        if (!$this->cache->hasItem($host)) { // "has" is important here, if "get" is used then cache item is created by symfony but has no value set
            return null;
        }

        $cacheItem = $this->cache->getItem($host);

        /** @var CachedWebsiteDto $dto */
        $dto = $this->serializer->deserialize($cacheItem->get(), CachedWebsiteDto::class, "json");

        return $dto;
    }

    /**
     * Will remove expired websites cache
     */
    public function removeExpired(): void
    {
        $this->cache->prune();
    }

    /**
     * Attempt to extract host from provided url
     *
     * @param string $url
     *
     * @return string
     */
    private function extractHost(string $url): string
    {
        $host = UrlHandlerService::getHost($url);
        if (empty($host)) {
            throw new LogicException("Failed extracting host from: {$url}, cannot store this url with content");
        }

        return $host;
    }
}
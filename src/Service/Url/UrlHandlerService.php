<?php

namespace CompanyDataProvider\Service\Url;

/**
 * Provides logic for handling urls,
 */
class UrlHandlerService
{
    /**
     * Returns host for the url, or null if host could not be extracted
     * Depending on url it returns whole www.domain.tld like "www.cookie.de"
     *
     * @param string $url
     *
     * @return string|null
     */
    public static function getHost(string $url): ?string
    {
        $host = parse_url($url, PHP_URL_HOST);
        if (empty($host)) {
            // basename returns the host if the url is really just the host
            $host = basename($url);
            if ($host !== $url) {
                return null;
            }
        }

        return $host;
    }

    /**
     * For any url it attempts to extract domain name only without "tld" etc.,
     * so for "www.cookie.com" the "cookie" part will be returned or null if no match was found
     *
     * Turns out extracting domain is NOT easy:
     * - {@link https://stackoverflow.com/questions/16027102/get-domain-name-from-full-url/16027164#16027164}
     * - {@link https://stackoverflow.com/questions/2679618/get-domain-name-not-subdomain-in-php}
     *
     * This function will also attempt to remove the subdomain
     *
     * @param string $url
     *
     * @return string|null
     */
    public static function getDomainOnly(string $url): ?string
    {
        $pieces = parse_url($url);
        $domain = $pieces['host'] ?? $pieces['path'];
        if (preg_match('/(www\.)?(?P<subdomain>[a-z0-9][a-z0-9\-]{1,63}\.)?(?P<domain>[a-z0-9][a-z0-9\-]{1,63})\.[a-z\.]{2,6}/i', $domain, $matches)) {
            return $matches['domain'];
        }

        return null;
    }

    /**
     * Will append the "http://" to the url if it does not start with "http".
     * If url has the protocol then original url is returned,
     * If url has no protocol then it will be appended to url, and modified url will be returned,
     *
     * @param string $url
     *
     * @return string
     */
    public static function appendHttp(string $url): string
    {
        if (!str_starts_with($url, "http")) {
            return "http://" . $url;
        }

        return $url;
    }
}
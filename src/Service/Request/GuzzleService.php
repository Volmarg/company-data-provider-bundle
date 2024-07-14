<?php

namespace CompanyDataProvider\Service\Request;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;

/**
 * Service for handling standard request via POST / GET etc.
 */
class GuzzleService implements GuzzleServiceInterface
{

    /**
     * @var Client $client
     */
    private Client $client;

    /**
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Will perform get request toward provided url
     *
     * @param string $url - url to be called
     * @param array  $options
     *
     * @return ResponseInterface
     * @throws GuzzleException
     */
    public function get(string $url, array $options = []): ResponseInterface
    {
        return $this->client->get($url, $options);
    }

    /**
     * Will perform post request toward provided url
     *
     * @param string $url - url to be called
     * @param array  $options
     *
     * @return ResponseInterface
     * @throws GuzzleException
     */
    public function post(string $url, array $options = []): ResponseInterface
    {
        return $this->client->post($url, $options);
    }

}
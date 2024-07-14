<?php

namespace CompanyDataProvider\Controller\Debug;

use CompanyDataProvider\Exception\Provider\ProviderException;
use CompanyDataProvider\Service\Provider\Email\EmailProviderService;
use Psr\Cache\CacheException;
use Psr\Cache\InvalidArgumentException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller for any kind of debugging
 */
class DebugController
{
    public function __construct(
        private readonly EmailProviderService $emailProviderService
    ){}

    /**
     * Debug route
     * @return never
     * @throws ProviderException
     * @throws CacheException
     * @throws InvalidArgumentException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Route("/debug", name: "debug")]
    public function debugRoute(): never
    {
        $result = $this->emailProviderService->getFromWebsite("os-cillation GmbH", null, "pol");
        dd($result);
    }
}
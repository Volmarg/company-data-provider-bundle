<?php

namespace CompanyDataProvider\Exception\Provider;

use Exception;
use Throwable;

/**
 * Indicates that something went wrong while trying to provide company data
 */
class ProviderException extends Exception
{
    public function __construct(Throwable $exception)
    {
        $message = "Something went wrong while trying to provide company data: {$exception->getMessage()} | Trace: {$exception->getTraceAsString()}";
        parent::__construct($message, $exception->getCode(), $exception);
    }

}
<?php

namespace CompanyDataProvider\Exception;

use Exception;

/**
 * Indicates that something went wrong while trying to obtain data from rating provider
 */
class RatingProviderException extends Exception
{
    /**
     * @param string $serviceName
     * @param string $extraMessage
     * @param string $stackTrace
     */
    public function __construct(string $serviceName, string $extraMessage = "", string $stackTrace = "")
    {
        $message = "Something went wrong while trying to obtain company rating using service: " . $serviceName;
        if (!empty($extraMessage)) {
            $message .= " . Additional information: {$extraMessage}. Stack trace: {$stackTrace}";
        }

        parent::__construct($message);
    }
}
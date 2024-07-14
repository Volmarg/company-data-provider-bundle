<?php

namespace CompanyDataProvider\Service\Request;

/**
 * Either defines logic for the {@see GuzzleService} or contains of logic which prevents bloating the main service
 */
interface GuzzleServiceInterface
{

    public const KEY_RAW_REQUEST_BODY  = "body";
    public const KEY_JSON_REQUEST_BODY = "json";
    public const KEY_HEADERS           = "headers";

}
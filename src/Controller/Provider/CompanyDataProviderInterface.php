<?php

namespace CompanyDataProvider\Controller\Provider;

/**
 * Defines common logic for classes extending from this interface or provides data for controller so that
 * it prevents it from bloating
 */
interface CompanyDataProviderInterface
{
    /**
     * Minimal similarity to consider the company and found domain being related
     */
    public const COMPANY_NAME_DOMAIN_MATCH_SIMILARITY_PERCENTAGE = 70;

    /**
     * Some companies have strange names like "Adhesive Ad. s. e. rgd. eczd." etc.
     * This defines what is the minimal length of a partial to consider it something really related to the company name
     */
    public const COMPANY_PARTIAL_NAME_MIN_LENGTH = 4;
}
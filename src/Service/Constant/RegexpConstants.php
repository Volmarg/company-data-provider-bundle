<?php

namespace CompanyDataProvider\Service\Constant;

class RegexpConstants
{
    public const LINKEDIN_COMPANY_URL                  = "(?<LINKEDIN_URL>http[s]?(?<SLASH_AFTER_PROTOCOL>:\/\/)?(?<WWW>[w]{0,3}\.)?(?<LANGUAGE_SUBDOMAIN>[a-z]{2,3}\.)?linkedin\.com\/company\/.*?)";
    public const LINKEDIN_COMPANY_URL_IN_TAG_ATTRIBUTE = '[\'"]{1}' . self::LINKEDIN_COMPANY_URL . '[\'"]{1}';
}
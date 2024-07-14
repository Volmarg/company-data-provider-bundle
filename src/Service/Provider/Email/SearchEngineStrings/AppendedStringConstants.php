<?php

namespace CompanyDataProvider\Service\Provider\Email\SearchEngineStrings;

use CompanyDataProvider\Service\Provider\Email\EmailProviderService;

/**
 * - {@see EmailProviderService::searchStringByString}
 *
 * Try not to extend the arrays too much, as each entry results in at least 2 additional curl calls
 */
class AppendedStringConstants
{
    public const GENERIC_STRING_EMAIL = "email";

    /**
     * Can be used as fallback in case when other used strings cannot be determined
     * Or can be just used alongside any other ones
     */
    public const GENERIC_STRINGS = [
        ...self::TOP_PRIORITY_GENERIC_STRING,
        ...self::LOWER_PRIORITY_GENERIC_STRING,
    ];

    public const TOP_PRIORITY_GENERIC_STRING = [
        " email +@",
        " email",
    ];

    public const LOWER_PRIORITY_GENERIC_STRING = [
        " contact",
        " impressum",
        " about"
    ];

    /**
     * Special strings for germany-localized search
     */
    public const GERMAN_STRINGS = [
        " kontakt",
        " unternehmen",
        " karriere"
    ];

    /**
     * Special strings for france-localized search
     */
    public const FRENCH_STRINGS = [
        " carrière",
        " entreprise",
    ];

    /**
     * Special strings for poland-localized search
     */
    public const POLISH_STRINGS = [
        " kontakt",
        " o nas",
        " kariera",
        " praca"
    ];

    /**
     * Special strings for spanish-localized search
     */
    public const SPANISH_STRINGS = [
        " contacto",
        " carrera",
    ];

    /**
     * Special strings for swedish-localized search
     */
    public const SWEDISH_STRINGS = [
        ' karriär',
        ' kontakt',
    ];

    /**
     * Special strings for norway-localized search
     */
    public const NORWAY_STRINGS = [
        ' karriere',
        ' kontakt',
    ];

}
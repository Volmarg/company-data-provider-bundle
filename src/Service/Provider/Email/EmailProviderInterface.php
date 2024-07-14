<?php

namespace CompanyDataProvider\Service\Provider\Email;

/**
 * Either defines logic required to provide E-Mails, or consist of data preventing classes from bloating
 */
interface EmailProviderInterface
{
    /**
     * This array decides which E-Mails are going to be considered `valid` for sending job applications
     * Priority matters, meaning that first found match is used as contact E-Mail,
     *
     * The grouping is for easier management, groups names are not being used anywhere,
     * Yet the order of groups matters as these will be processed one after another
     */
    public const JOB_APPLICATION_EMAIL_PREFERRED_SUBSTRINGS = [
        "general" => [
            "hello",
            "hallo",
            "job",
            "jobs",
            "hr",
            "contact",
            "info",
            "office",
            "website",
            "mail",
        ],
        "eng" => [
            "post",
            "work",
            "hi",
            "welcome"
        ],
        "de" => [
            "moin",
            "arbeit",
        ],
        "pl" => [
            "kontakt",
            "praca",
            "zatrudnienie",
            "biuro",
            "kadry",
        ],
        "esp" => [
            "contactenos",
            "recrutement",
        ]
    ];

    /**
     * In some cases it's really hard to determine if the E-Mail is suitable for job applications,
     * These strings help to define which from the emails can eventually be considered fine for applying job offers
     * by excluding these that are not job offer application related at all,
     *
     * Grouping was added just for easier management
     */
    public const JOB_APPLICATION_EMAIL_EXCLUDE_STRINGS = [
        "general" => [
            "data protection",
            "dataprotection",
            "data security",
            "datasecurity",
            "training",
            "advice",
            "webmaster",
            "press",
            "pressoffice",
            "assistance",
            "accommodations",
            "security",
            "responsibility",
            "privacy",
            "dpo", // often referred as data-protection-office
            "media",
            "client",
        ],
        "pol" => [
            "faktura",
            "recepcja",
            "prasa",
        ],
        "eng" => [
            "fan", // for example excludes: "fan-base"
            "team",
            "shop",
            "support",
            "service",
            "feedback",
            "sales",
            "help",
            "admin",
            "recharge",
            "answers",
            "invest", // for example excludes: "Investor / Investments"
        ],
        "de" => [
            "impressum",
            "frage",
            "abmeld",
            "presse",
            "patent",
            "datenschutz",
        ],
        "esp" => [
            "controlciudadano",
            "judiciales",
            "servicio",
            "autorizacion",
        ],
        "fra" => [
            "partenaire",
            "allocataires",
            "reductions",
            "recouvrement",
            "contentieux",
            "affiliations",
            "comptabilite",
            "entrepreneur",
            "actionnaires",
        ]
    ];
}
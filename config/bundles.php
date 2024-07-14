<?php

return [
    Symfony\Bundle\FrameworkBundle\FrameworkBundle::class => ['all' => true],
    WebScrapperBundle\WebScrapperBundle::class => ['all' => true],
    SearchEngineProvider\SearchEngineProviderBundle::class => ['all' => true],
    Symfony\Bundle\DebugBundle\DebugBundle::class => ['dev' => true],
    DataParser\DataParserBundle::class => ['all' => true],
    SmtpEmailValidatorBundle\SmtpEmailValidatorBundle::class => ['all' => true],
    Symfony\Bundle\MonologBundle\MonologBundle::class => ['all' => true],
];

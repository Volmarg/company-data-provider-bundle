<?php

namespace CompanyDataProvider\Service\System;

class EnvReader
{
    const VAR_APP_ENV       = "APP_ENV";
    const APP_ENV_MODE_DEV  = "dev";
    const APP_ENV_MODE_PROD = "prod";

    /**
     * Check if the project runs on the production system
     *
     * @return bool
     */
    public static function isProd(): bool
    {
        return ($_ENV[self::VAR_APP_ENV] === self::APP_ENV_MODE_PROD);
    }

    /**
     * Check if the project runs on the development system
     *
     * @return bool
     */
    public static function isDev(): bool
    {
        return ($_ENV[self::VAR_APP_ENV] === self::APP_ENV_MODE_DEV);
    }

    /**
     * Check if proxy should be enabled or not
     *
     * @return bool
     */
    public static function isProxyEnabled(): bool
    {
        if (!isset($_ENV['IS_PROXY_ENABLED'])) {
            return false;
        }

        return ($_ENV['IS_PROXY_ENABLED'] == 'true' || $_ENV['IS_PROXY_ENABLED'] == 1 || $_ENV['IS_PROXY_ENABLED'] === true);
    }

}

<?php

namespace CompanyDataProvider\Service\AllowanceChecker;

/**
 * Generic interface that can be used (can be overwritten from the parent project) to decide if something
 * can be done (when perspective of parent will check is something can be done in child project scope)
 */
interface AllowanceCheckerInterface
{
    /**
     * Check if is allowed (literally anything)
     *
     * @param mixed $checkedData
     *
     * @return bool
     */
    public function isAllowed(mixed $checkedData): bool;
}
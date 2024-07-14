<?php

namespace CompanyDataProvider\Service\AllowanceChecker;

class DefaultAllowanceChecker implements AllowanceCheckerInterface
{
    /**
     * {@inheritDoc}
     */
    public function isAllowed(mixed $checkedData): bool
    {
        return true;
    }
}
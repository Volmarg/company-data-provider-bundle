<?php

namespace CompanyDataProvider\Service\TypeProcessor;

/**
 * Handler of array related logic
 */
class ArrayTypeProcessor
{
    /**
     * Performs array_unique but case insensitive
     *
     * @param array $handledArray
     *
     * @return mixed
     */
    public static function array_iunique(array $handledArray): array
    {
        $lowerCaseValues = array_map('strtolower', $handledArray);
        $cleanedTopics   = array_unique($lowerCaseValues);

        foreach ($handledArray as $key => $value) {
            if (!isset($cleanedTopics[$key])) {
                unset($handledArray[$key]);
            }
        }

        return $handledArray;
    }

}
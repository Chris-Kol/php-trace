<?php

namespace PhpTrace\Detector;

/**
 * Detects trace enablement via query parameter
 */
class QueryDetector implements DetectorInterface
{
    /**
     * @param array<string, mixed> $queryParams
     */
    public function __construct(
        private array $queryParams = [],
        private string $parameterName = 'TRACE',
        private string $expectedValue = '1'
    ) {
        // If no query params provided, use $_GET
        if (empty($this->queryParams)) {
            $this->queryParams = $_GET;
        }
    }

    public function isEnabled(): bool
    {
        if (!isset($this->queryParams[$this->parameterName])) {
            return false;
        }

        return $this->queryParams[$this->parameterName] === $this->expectedValue;
    }
}

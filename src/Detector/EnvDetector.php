<?php

namespace PhpTrace\Detector;

/**
 * Detects trace enablement via environment variable
 */
class EnvDetector implements DetectorInterface
{
    public function __construct(
        private string $variableName = 'TRACE',
        private string $expectedValue = '1'
    ) {
    }

    public function isEnabled(): bool
    {
        $value = getenv($this->variableName);
        return $value === $this->expectedValue;
    }
}

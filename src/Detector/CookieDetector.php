<?php

namespace PhpTrace\Detector;

/**
 * Detects trace enablement via cookie
 */
class CookieDetector implements DetectorInterface
{
    /**
     * @param array<string, mixed> $cookies
     */
    public function __construct(
        private array $cookies = [],
        private string $cookieName = 'TRACE',
        private string $expectedValue = '1'
    ) {
        // If no cookies provided, use $_COOKIE
        if (empty($this->cookies)) {
            $this->cookies = $_COOKIE;
        }
    }

    public function isEnabled(): bool
    {
        if (!isset($this->cookies[$this->cookieName])) {
            return false;
        }

        return $this->cookies[$this->cookieName] === $this->expectedValue;
    }
}

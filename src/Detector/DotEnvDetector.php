<?php

namespace PhpTrace\Detector;

/**
 * Detects trace enablement via .env file
 */
class DotEnvDetector implements DetectorInterface
{
    public function __construct(
        private string $variableName = 'TRACE',
        private string $expectedValue = '1',
        private int $maxLevels = 5
    ) {
    }

    public function isEnabled(): bool
    {
        $currentDir = getcwd();
        if ($currentDir === false) {
            return false;
        }

        for ($i = 0; $i < $this->maxLevels; $i++) {
            $envFile = $currentDir . '/.env';

            if (file_exists($envFile)) {
                $envContent = file_get_contents($envFile);
                if ($envContent === false) {
                    continue;
                }

                // Match: VARIABLE_NAME=VALUE (with optional whitespace)
                $pattern = sprintf(
                    '/^\s*%s\s*=\s*%s\s*$/m',
                    preg_quote($this->variableName, '/'),
                    preg_quote($this->expectedValue, '/')
                );

                if (preg_match($pattern, $envContent)) {
                    return true;
                }
            }

            $parentDir = dirname($currentDir);
            if ($parentDir === $currentDir) {
                break;
            }
            $currentDir = $parentDir;
        }

        return false;
    }
}

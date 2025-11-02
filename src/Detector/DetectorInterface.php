<?php

namespace PhpTrace\Detector;

/**
 * Interface for trace enablement detection
 */
interface DetectorInterface
{
    /**
     * Check if tracing should be enabled
     */
    public function isEnabled(): bool;
}

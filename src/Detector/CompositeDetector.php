<?php

namespace PhpTrace\Detector;

/**
 * Composite detector that checks multiple detection sources
 * Returns true if ANY detector reports enabled
 */
class CompositeDetector implements DetectorInterface
{
    /**
     * @param array<DetectorInterface> $detectors
     */
    public function __construct(
        private array $detectors = []
    ) {
    }

    public function isEnabled(): bool
    {
        foreach ($this->detectors as $detector) {
            if ($detector->isEnabled()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Add a detector to the composite
     */
    public function addDetector(DetectorInterface $detector): void
    {
        $this->detectors[] = $detector;
    }

    /**
     * Get all detectors
     *
     * @return array<DetectorInterface>
     */
    public function getDetectors(): array
    {
        return $this->detectors;
    }
}

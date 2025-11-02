<?php

namespace PhpTrace\Tracer;

/**
 * Xdebug-based tracer implementation
 */
class XdebugTracer implements TracerInterface
{
    private ?string $activeTraceFile = null;

    public function __construct(
        private bool $collectParams = false,
        private bool $collectReturn = false,
        private bool $collectAssignments = false
    ) {
    }

    public function start(string $outputFile): void
    {
        if (!extension_loaded('xdebug')) {
            throw new \RuntimeException('Xdebug extension is not loaded');
        }

        if (!function_exists('xdebug_start_trace')) {
            throw new \RuntimeException(
                'xdebug_start_trace() function not available. ' .
                'Ensure Xdebug is properly configured with trace support.'
            );
        }

        // Configure Xdebug
        ini_set('xdebug.mode', 'trace');
        ini_set('xdebug.trace_format', '1'); // Format 1 = computer-readable
        ini_set('xdebug.collect_params', $this->collectParams ? '1' : '0');
        ini_set('xdebug.collect_return', $this->collectReturn ? '1' : '0');
        ini_set('xdebug.collect_assignments', $this->collectAssignments ? '1' : '0');

        // Start tracing
        xdebug_start_trace($outputFile);
        $this->activeTraceFile = $outputFile;
    }

    public function stop(): string
    {
        if ($this->activeTraceFile === null) {
            throw new \RuntimeException('No active trace to stop');
        }

        if (!function_exists('xdebug_stop_trace')) {
            throw new \RuntimeException('xdebug_stop_trace() function not available');
        }

        // Stop the trace - xdebug_stop_trace returns the trace file path
        // In Xdebug 3+, it returns void, so we use the stored active trace file
        xdebug_stop_trace();

        $tracePath = $this->activeTraceFile;
        $this->activeTraceFile = null;

        // The actual file will have .xt or .xt.gz extension
        return $tracePath;
    }

    /**
     * Check if Xdebug is available and properly configured
     */
    public function isAvailable(): bool
    {
        return extension_loaded('xdebug')
            && function_exists('xdebug_start_trace')
            && function_exists('xdebug_stop_trace');
    }

    /**
     * Get the currently active trace file path
     */
    public function getActiveTraceFile(): ?string
    {
        return $this->activeTraceFile;
    }
}

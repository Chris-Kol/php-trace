<?php

namespace PhpTrace\Tracer;

/**
 * Interface for trace execution (starting and stopping traces)
 */
interface TracerInterface
{
    /**
     * Start tracing execution
     *
     * @param string $outputFile Path where trace file will be written
     */
    public function start(string $outputFile): void;

    /**
     * Stop tracing execution
     *
     * @return string Path to the generated trace file
     */
    public function stop(): string;
}

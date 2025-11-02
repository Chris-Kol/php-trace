<?php

namespace PhpTrace;

use PhpTrace\Config\TraceConfig;
use PhpTrace\Detector\DetectorInterface;
use PhpTrace\Formatter\FormatterInterface;
use PhpTrace\Parser\ParserInterface;
use PhpTrace\Tracer\TracerInterface;
use PhpTrace\Writer\WriterInterface;

/**
 * Main orchestrator for the tracing process
 */
class TraceManager
{
    /**
     * @param array<FormatterInterface> $formatters
     */
    public function __construct(
        private DetectorInterface $detector,
        private TracerInterface $tracer,
        private ParserInterface $parser,
        private array $formatters,
        private WriterInterface $writer,
        private TraceConfig $config
    ) {
    }

    /**
     * Execute the tracing process
     */
    public function execute(): void
    {
        // Check if tracing is enabled
        if (!$this->detector->isEnabled()) {
            return;
        }

        // Get output directory
        $outputDir = $this->getOutputDirectory();

        // Ensure output directory exists
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        // Generate trace filename
        $timestamp = date('Y-m-d_H-i-s');
        $requestInfo = $this->getRequestInfo();
        $traceFile = $outputDir . '/php-trace-' . $timestamp . $requestInfo;

        try {
            // Start tracing
            $this->tracer->start($traceFile);

            // Register shutdown function to process trace
            register_shutdown_function(function () use ($traceFile, $outputDir, $timestamp) {
                $this->processTrace($traceFile, $outputDir, $timestamp);
            });
        } catch (\RuntimeException $e) {
            error_log('[PHP-Trace] Failed to start tracing: ' . $e->getMessage());
        }
    }

    /**
     * Process the trace file after execution
     */
    private function processTrace(string $traceFile, string $outputDir, string $timestamp): void
    {
        try {
            // Stop tracing
            $this->tracer->stop();

            // Find the actual trace file (could be .xt or .xt.gz)
            $actualTraceFile = $this->findTraceFile($traceFile);

            if ($actualTraceFile === null) {
                error_log('[PHP-Trace] Trace file not found: ' . $traceFile);
                return;
            }

            // Parse the trace
            $traceData = $this->parser->parse($actualTraceFile);

            // Format and write output files
            foreach ($this->formatters as $formatter) {
                $formattedOutput = $formatter->format($traceData);
                $extension = $formatter->getExtension();
                $outputFile = $outputDir . '/php-trace-' . $timestamp . '.' . $extension;

                $this->writer->write($formattedOutput, $outputFile);

                $this->logOutput($outputFile);
            }

            // Clean up raw trace files
            $this->cleanupTraceFiles($traceFile);
        } catch (\Throwable $e) {
            error_log('[PHP-Trace] Error processing trace: ' . $e->getMessage());
        }
    }

    /**
     * Find the trace file (handles .xt and .xt.gz extensions)
     */
    private function findTraceFile(string $baseFile): ?string
    {
        if (file_exists($baseFile . '.xt')) {
            return $baseFile . '.xt';
        }

        if (file_exists($baseFile . '.xt.gz')) {
            // Decompress gzipped trace
            $gzContent = file_get_contents($baseFile . '.xt.gz');
            if ($gzContent !== false) {
                $content = gzdecode($gzContent);
                if ($content !== false) {
                    $decompressedFile = $baseFile . '.xt';
                    file_put_contents($decompressedFile, $content);
                    return $decompressedFile;
                }
            }
        }

        return null;
    }

    /**
     * Clean up temporary trace files
     */
    private function cleanupTraceFiles(string $baseFile): void
    {
        if (file_exists($baseFile . '.xt')) {
            unlink($baseFile . '.xt');
        }

        if (file_exists($baseFile . '.xt.gz')) {
            unlink($baseFile . '.xt.gz');
        }
    }

    /**
     * Get output directory with priority: ENV > config > default
     */
    private function getOutputDirectory(): string
    {
        $outputDir = getenv('TRACE_OUTPUT_DIR');

        if (!$outputDir) {
            $outputDir = $this->config->getOutputDir();

            // Make it absolute if it's relative
            if (!str_starts_with($outputDir, '/')) {
                $currentDir = getcwd();
                if ($currentDir !== false) {
                    $outputDir = $currentDir . '/' . $outputDir;
                } else {
                    $outputDir = '/tmp';
                }
            }
        }

        return $outputDir;
    }

    /**
     * Get request info for filename (web requests only)
     */
    private function getRequestInfo(): string
    {
        if (!isset($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI'])) {
            return '';
        }

        $method = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $uri = is_string($uri) ? str_replace('/', '_', trim($uri, '/')) : '';
        $uri = $uri !== '' ? $uri : 'index';

        return '-' . strtolower($method) . '-' . $uri;
    }

    /**
     * Log output file path
     */
    private function logOutput(string $outputFile): void
    {
        if (defined('STDERR')) {
            static $headerPrinted = false;
            if (!$headerPrinted) {
                fwrite(STDERR, "\n[PHP-Trace] Trace files generated:\n");
                $headerPrinted = true;
            }
            fwrite(STDERR, "[PHP-Trace]   {$outputFile}\n");
        } else {
            error_log("[PHP-Trace] Trace file generated: {$outputFile}");
        }
    }
}

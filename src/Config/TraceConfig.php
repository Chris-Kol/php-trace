<?php

namespace PhpTrace\Config;

/**
 * Configuration for PHP-Trace
 */
class TraceConfig
{
    /**
     * @param array<string> $excludePatterns Patterns to exclude from traces
     * @param string $outputDir Directory to write trace files
     * @param array<string> $formats Output formats to generate
     */
    public function __construct(
        private array $excludePatterns = ['vendor/', 'composer/'],
        private string $outputDir = 'traces',
        private array $formats = ['json', 'markdown']
    ) {
    }

    /**
     * @return array<string>
     */
    public function getExcludePatterns(): array
    {
        return $this->excludePatterns;
    }

    public function getOutputDir(): string
    {
        return $this->outputDir;
    }

    /**
     * @return array<string>
     */
    public function getFormats(): array
    {
        return $this->formats;
    }

    /**
     * Load configuration from a file
     */
    public static function fromFile(string $configFile): self
    {
        if (!file_exists($configFile)) {
            throw new \RuntimeException(
                "Config file not found: {$configFile}"
            );
        }

        try {
            $config = require $configFile;
        } catch (\Throwable $e) {
            throw new \RuntimeException(
                "Failed to load config file: {$configFile}",
                0,
                $e
            );
        }

        if (!is_array($config)) {
            throw new \RuntimeException('Config file must return an array');
        }

        return new self(
            excludePatterns: $config['exclude_patterns'] ?? ['vendor/', 'composer/'],
            outputDir: $config['output_dir'] ?? 'traces',
            formats: $config['formats'] ?? ['json', 'markdown']
        );
    }

    /**
     * Load configuration with auto-discovery
     */
    public static function load(): self
    {
        // Look for phptrace.php in current directory and parent directories
        $currentDir = getcwd();

        if ($currentDir === false) {
            return new self();
        }

        $maxLevels = 5;

        for ($i = 0; $i < $maxLevels; $i++) {
            $configFile = $currentDir . '/phptrace.php';

            if (file_exists($configFile)) {
                return self::fromFile($configFile);
            }

            $parentDir = dirname($currentDir);

            if ($parentDir === $currentDir) {
                break;
            }

            $currentDir = $parentDir;
        }

        // No config file found, use defaults
        return new self();
    }
}

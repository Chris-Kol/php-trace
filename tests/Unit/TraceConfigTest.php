<?php

namespace PhpTrace\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PhpTrace\Config\TraceConfig;

class TraceConfigTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();
        // Create a temporary directory for test config files
        $this->tempDir = sys_get_temp_dir() . '/php-trace-test-' . uniqid();
        mkdir($this->tempDir);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        // Clean up temporary directory
        if (is_dir($this->tempDir)) {
            $this->removeDirectory($this->tempDir);
        }
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $scannedFiles = scandir($dir);
        if ($scannedFiles === false) {
            return;
        }

        $files = array_diff($scannedFiles, ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }

    public function test__construct__with_defaults(): void
    {
        $config = new TraceConfig();

        $this->assertEquals(['vendor/', 'composer/'], $config->getExcludePatterns());
        $this->assertEquals('traces', $config->getOutputDir());
        $this->assertEquals(['json', 'markdown'], $config->getFormats());
    }

    public function test__construct__with_custom_values(): void
    {
        $config = new TraceConfig(
            excludePatterns: ['vendor/', 'tests/'],
            outputDir: 'custom-traces',
            formats: ['json']
        );

        $this->assertEquals(['vendor/', 'tests/'], $config->getExcludePatterns());
        $this->assertEquals('custom-traces', $config->getOutputDir());
        $this->assertEquals(['json'], $config->getFormats());
    }

    public function test__fromFile__loads_config_successfully(): void
    {
        $configFile = $this->tempDir . '/phptrace.php';
        file_put_contents($configFile, <<<'PHP'
<?php
return [
    'exclude_patterns' => ['vendor/', 'node_modules/'],
    'output_dir' => 'my-traces',
    'formats' => ['markdown'],
];
PHP
        );

        $config = TraceConfig::fromFile($configFile);

        $this->assertEquals(['vendor/', 'node_modules/'], $config->getExcludePatterns());
        $this->assertEquals('my-traces', $config->getOutputDir());
        $this->assertEquals(['markdown'], $config->getFormats());
    }

    public function test__fromFile__uses_defaults_for_missing_keys(): void
    {
        $configFile = $this->tempDir . '/phptrace.php';
        file_put_contents($configFile, <<<'PHP'
<?php
return [
    'exclude_patterns' => ['custom/'],
];
PHP
        );

        $config = TraceConfig::fromFile($configFile);

        $this->assertEquals(['custom/'], $config->getExcludePatterns());
        $this->assertEquals('traces', $config->getOutputDir()); // default
        $this->assertEquals(['json', 'markdown'], $config->getFormats()); // default
    }

    public function test__fromFile__handles_empty_config_file(): void
    {
        $configFile = $this->tempDir . '/phptrace.php';
        file_put_contents($configFile, <<<'PHP'
<?php
return [];
PHP
        );

        $config = TraceConfig::fromFile($configFile);

        $this->assertEquals(['vendor/', 'composer/'], $config->getExcludePatterns());
        $this->assertEquals('traces', $config->getOutputDir());
        $this->assertEquals(['json', 'markdown'], $config->getFormats());
    }

    public function test__fromFile__throws_exception_for_nonexistent_file(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Config file not found');

        TraceConfig::fromFile('/nonexistent/path/phptrace.php');
    }

    public function test__fromFile__throws_exception_for_invalid_php(): void
    {
        $configFile = $this->tempDir . '/phptrace.php';
        file_put_contents($configFile, '<?php invalid syntax here');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to load config file');

        TraceConfig::fromFile($configFile);
    }

    public function test__fromFile__throws_exception_when_not_returning_array(): void
    {
        $configFile = $this->tempDir . '/phptrace.php';
        file_put_contents($configFile, <<<'PHP'
<?php
return "not an array";
PHP
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Config file must return an array');

        TraceConfig::fromFile($configFile);
    }

    public function test__load__finds_config_in_current_directory(): void
    {
        $configFile = $this->tempDir . '/phptrace.php';
        file_put_contents($configFile, <<<'PHP'
<?php
return [
    'exclude_patterns' => ['found-in-current/'],
];
PHP
        );

        $originalDir = getcwd();
        if ($originalDir === false) {
            $this->markTestSkipped('Cannot get current directory');
        }

        chdir($this->tempDir);

        try {
            $config = TraceConfig::load();
            $this->assertEquals(['found-in-current/'], $config->getExcludePatterns());
        } finally {
            chdir($originalDir);
        }
    }

    public function test__load__finds_config_in_parent_directory(): void
    {
        $configFile = $this->tempDir . '/phptrace.php';
        file_put_contents($configFile, <<<'PHP'
<?php
return [
    'exclude_patterns' => ['found-in-parent/'],
];
PHP
        );

        $subDir = $this->tempDir . '/subdir';
        mkdir($subDir);

        $originalDir = getcwd();
        if ($originalDir === false) {
            $this->markTestSkipped('Cannot get current directory');
        }

        chdir($subDir);

        try {
            $config = TraceConfig::load();
            $this->assertEquals(['found-in-parent/'], $config->getExcludePatterns());
        } finally {
            chdir($originalDir);
        }
    }

    public function test__load__uses_defaults_when_no_config_found(): void
    {
        $originalDir = getcwd();
        if ($originalDir === false) {
            $this->markTestSkipped('Cannot get current directory');
        }

        chdir($this->tempDir);

        try {
            $config = TraceConfig::load();
            $this->assertEquals(['vendor/', 'composer/'], $config->getExcludePatterns());
            $this->assertEquals('traces', $config->getOutputDir());
            $this->assertEquals(['json', 'markdown'], $config->getFormats());
        } finally {
            chdir($originalDir);
        }
    }

    public function test__load__stops_searching_at_max_depth(): void
    {
        // Create config at root
        $rootConfigFile = $this->tempDir . '/phptrace.php';
        file_put_contents($rootConfigFile, <<<'PHP'
<?php
return [
    'exclude_patterns' => ['root-level/'],
];
PHP
        );

        // Create deep subdirectory (more than 5 levels)
        $deepDir = $this->tempDir . '/a/b/c/d/e/f';
        mkdir($deepDir, 0777, true);

        $originalDir = getcwd();
        if ($originalDir === false) {
            $this->markTestSkipped('Cannot get current directory');
        }

        chdir($deepDir);

        try {
            // Should use defaults because we're too deep
            $config = TraceConfig::load();
            $this->assertEquals(['vendor/', 'composer/'], $config->getExcludePatterns());
        } finally {
            chdir($originalDir);
        }
    }

    public function test__getExcludePatterns__returns_array(): void
    {
        $config = new TraceConfig(excludePatterns: ['test1/', 'test2/']);

        $patterns = $config->getExcludePatterns();

        $this->assertIsArray($patterns);
        $this->assertCount(2, $patterns);
    }

    public function test__getOutputDir__returns_string(): void
    {
        $config = new TraceConfig(outputDir: 'custom-dir');

        $dir = $config->getOutputDir();

        $this->assertIsString($dir);
        $this->assertEquals('custom-dir', $dir);
    }

    public function test__getFormats__returns_array(): void
    {
        $config = new TraceConfig(formats: ['json', 'xml']);

        $formats = $config->getFormats();

        $this->assertIsArray($formats);
        $this->assertCount(2, $formats);
    }
}

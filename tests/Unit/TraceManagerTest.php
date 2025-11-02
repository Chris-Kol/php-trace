<?php

namespace PhpTrace\Tests\Unit;

use Mockery;
use PHPUnit\Framework\TestCase;
use PhpTrace\Config\TraceConfig;
use PhpTrace\Detector\DetectorInterface;
use PhpTrace\Formatter\FormatterInterface;
use PhpTrace\Parser\ParserInterface;
use PhpTrace\TraceManager;
use PhpTrace\Tracer\TracerInterface;
use PhpTrace\Writer\WriterInterface;

class TraceManagerTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
        putenv('TRACE_OUTPUT_DIR');
    }

    public function test__execute__returns_early_when_detector_disabled(): void
    {
        $detector = Mockery::mock(DetectorInterface::class);
        $detector->shouldReceive('isEnabled')->once()->andReturn(false);

        $tracer = Mockery::mock(TracerInterface::class);
        $tracer->shouldReceive('start')->never();

        $parser = Mockery::mock(ParserInterface::class);
        $writer = Mockery::mock(WriterInterface::class);
        $config = new TraceConfig();

        $manager = new TraceManager($detector, $tracer, $parser, [], $writer, $config);

        $manager->execute();

        // If we get here without exceptions, the test passed
        $this->assertTrue(true);
    }

    public function test__execute__starts_tracing_when_detector_enabled(): void
    {
        $detector = Mockery::mock(DetectorInterface::class);
        $detector->shouldReceive('isEnabled')->once()->andReturn(true);

        $tracer = Mockery::mock(TracerInterface::class);
        $tracer->shouldReceive('start')
            ->once()
            ->with(Mockery::on(function ($arg) {
                return is_string($arg) && str_contains($arg, 'php-trace-');
            }));
        $tracer->shouldReceive('stop')->zeroOrMoreTimes();

        $parser = Mockery::mock(ParserInterface::class);
        $writer = Mockery::mock(WriterInterface::class);
        $config = new TraceConfig();

        $manager = new TraceManager($detector, $tracer, $parser, [], $writer, $config);

        $manager->execute();

        $this->assertTrue(true);
    }

    public function test__execute__handles_tracer_exception_gracefully(): void
    {
        $detector = Mockery::mock(DetectorInterface::class);
        $detector->shouldReceive('isEnabled')->once()->andReturn(true);

        $tracer = Mockery::mock(TracerInterface::class);
        $tracer->shouldReceive('start')
            ->once()
            ->andThrow(new \RuntimeException('Xdebug not available'));

        $parser = Mockery::mock(ParserInterface::class);
        $writer = Mockery::mock(WriterInterface::class);
        $config = new TraceConfig();

        $manager = new TraceManager($detector, $tracer, $parser, [], $writer, $config);

        // Should not throw exception, just log error
        $manager->execute();

        $this->assertTrue(true);
    }

    public function test__constructor__accepts_all_dependencies(): void
    {
        $detector = Mockery::mock(DetectorInterface::class);
        $tracer = Mockery::mock(TracerInterface::class);
        $parser = Mockery::mock(ParserInterface::class);
        $formatter1 = Mockery::mock(FormatterInterface::class);
        $formatter2 = Mockery::mock(FormatterInterface::class);
        $writer = Mockery::mock(WriterInterface::class);
        $config = new TraceConfig();

        $manager = new TraceManager(
            $detector,
            $tracer,
            $parser,
            [$formatter1, $formatter2],
            $writer,
            $config
        );

        $this->assertInstanceOf(TraceManager::class, $manager);
    }

    public function test__constructor__accepts_empty_formatters_array(): void
    {
        $detector = Mockery::mock(DetectorInterface::class);
        $tracer = Mockery::mock(TracerInterface::class);
        $parser = Mockery::mock(ParserInterface::class);
        $writer = Mockery::mock(WriterInterface::class);
        $config = new TraceConfig();

        $manager = new TraceManager(
            $detector,
            $tracer,
            $parser,
            [],
            $writer,
            $config
        );

        $this->assertInstanceOf(TraceManager::class, $manager);
    }

    public function test__execute__uses_custom_output_dir_from_env(): void
    {
        $tempDir = sys_get_temp_dir() . '/php-trace-manager-test-' . uniqid();
        mkdir($tempDir);

        try {
            putenv("TRACE_OUTPUT_DIR={$tempDir}");

            $detector = Mockery::mock(DetectorInterface::class);
            $detector->shouldReceive('isEnabled')->once()->andReturn(true);

            $tracer = Mockery::mock(TracerInterface::class);
            $tracer->shouldReceive('start')
                ->once()
                ->with(Mockery::on(function ($arg) use ($tempDir) {
                    return str_starts_with($arg, $tempDir . '/');
                }));
            $tracer->shouldReceive('stop')->zeroOrMoreTimes();

            $parser = Mockery::mock(ParserInterface::class);
            $writer = Mockery::mock(WriterInterface::class);
            $config = new TraceConfig();

            $manager = new TraceManager($detector, $tracer, $parser, [], $writer, $config);

            $manager->execute();

            $this->assertTrue(true);
        } finally {
            if (is_dir($tempDir)) {
                rmdir($tempDir);
            }
        }
    }

    public function test__execute__uses_config_output_dir_when_no_env(): void
    {
        putenv('TRACE_OUTPUT_DIR'); // Clear env var

        $detector = Mockery::mock(DetectorInterface::class);
        $detector->shouldReceive('isEnabled')->once()->andReturn(true);

        $tracer = Mockery::mock(TracerInterface::class);
        $tracer->shouldReceive('start')
            ->once()
            ->with(Mockery::on(function ($arg) {
                // Should contain 'traces' from config default
                return str_contains($arg, 'traces/php-trace-');
            }));
        $tracer->shouldReceive('stop')->zeroOrMoreTimes();

        $parser = Mockery::mock(ParserInterface::class);
        $writer = Mockery::mock(WriterInterface::class);
        $config = new TraceConfig(outputDir: 'traces');

        $manager = new TraceManager($detector, $tracer, $parser, [], $writer, $config);

        $manager->execute();

        $this->assertTrue(true);
    }

    public function test__execute__includes_request_info_in_filename_for_web(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/api/users';

        $detector = Mockery::mock(DetectorInterface::class);
        $detector->shouldReceive('isEnabled')->once()->andReturn(true);

        $tracer = Mockery::mock(TracerInterface::class);
        $tracer->shouldReceive('start')
            ->once()
            ->with(Mockery::on(function ($arg) {
                // Should contain request method and path
                return str_contains($arg, '-get-') && str_contains($arg, 'api_users');
            }));
        $tracer->shouldReceive('stop')->zeroOrMoreTimes();

        $parser = Mockery::mock(ParserInterface::class);
        $writer = Mockery::mock(WriterInterface::class);
        $config = new TraceConfig();

        $manager = new TraceManager($detector, $tracer, $parser, [], $writer, $config);

        $manager->execute();

        unset($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);

        $this->assertTrue(true);
    }

    public function test__execute__handles_cli_requests_without_request_info(): void
    {
        unset($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);

        $detector = Mockery::mock(DetectorInterface::class);
        $detector->shouldReceive('isEnabled')->once()->andReturn(true);

        $tracer = Mockery::mock(TracerInterface::class);
        $tracer->shouldReceive('start')
            ->once()
            ->with(Mockery::on(function ($arg) {
                // Should NOT contain request info for CLI
                return !str_contains($arg, '-get-') && !str_contains($arg, '-post-');
            }));
        $tracer->shouldReceive('stop')->zeroOrMoreTimes();

        $parser = Mockery::mock(ParserInterface::class);
        $writer = Mockery::mock(WriterInterface::class);
        $config = new TraceConfig();

        $manager = new TraceManager($detector, $tracer, $parser, [], $writer, $config);

        $manager->execute();

        $this->assertTrue(true);
    }
}

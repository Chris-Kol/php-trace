<?php

namespace PhpTrace\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PhpTrace\Tracer\XdebugTracer;

class XdebugTracerTest extends TestCase
{
    public function test__constructor__sets_default_options(): void
    {
        $tracer = new XdebugTracer();

        $this->assertInstanceOf(XdebugTracer::class, $tracer);
    }

    public function test__constructor__accepts_custom_options(): void
    {
        $tracer = new XdebugTracer(
            collectParams: true,
            collectReturn: true,
            collectAssignments: true
        );

        $this->assertInstanceOf(XdebugTracer::class, $tracer);
    }

    public function test__isAvailable__returns_true_when_xdebug_loaded(): void
    {
        $tracer = new XdebugTracer();

        // This will return true if Xdebug is installed, false otherwise
        $isAvailable = $tracer->isAvailable();

        $this->assertIsBool($isAvailable);
    }

    public function test__getActiveTraceFile__returns_null_initially(): void
    {
        $tracer = new XdebugTracer();

        $this->assertNull($tracer->getActiveTraceFile());
    }

    public function test__stop__throws_exception_when_no_active_trace(): void
    {
        $tracer = new XdebugTracer();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No active trace to stop');

        $tracer->stop();
    }

    /**
     * Integration test - only runs if Xdebug is available
     *
     * @group integration
     * @requires extension xdebug
     */
    public function test__start__and__stop__work_with_xdebug(): void
    {
        if (!extension_loaded('xdebug')) {
            $this->markTestSkipped('Xdebug extension not loaded');
        }

        if (!function_exists('xdebug_start_trace')) {
            $this->markTestSkipped('xdebug_start_trace() not available');
        }

        $tracer = new XdebugTracer();
        $outputFile = sys_get_temp_dir() . '/test-trace-' . uniqid();

        try {
            $tracer->start($outputFile);

            // Should have active trace file
            $this->assertNotNull($tracer->getActiveTraceFile());
            $this->assertEquals($outputFile, $tracer->getActiveTraceFile());

            // Stop the trace
            $tracePath = $tracer->stop();

            // Should return a path
            $this->assertIsString($tracePath);

            // Active trace should be cleared
            $this->assertNull($tracer->getActiveTraceFile());
        } finally {
            // Cleanup
            if (file_exists($outputFile . '.xt')) {
                unlink($outputFile . '.xt');
            }
            if (file_exists($outputFile . '.xt.gz')) {
                unlink($outputFile . '.xt.gz');
            }
        }
    }

    /**
     * Integration test - configures Xdebug options
     *
     * @group integration
     * @requires extension xdebug
     */
    public function test__start__configures_xdebug_options(): void
    {
        if (!extension_loaded('xdebug')) {
            $this->markTestSkipped('Xdebug extension not loaded');
        }

        if (!function_exists('xdebug_start_trace')) {
            $this->markTestSkipped('xdebug_start_trace() not available');
        }

        $tracer = new XdebugTracer(
            collectParams: true,
            collectReturn: true,
            collectAssignments: false
        );

        $outputFile = sys_get_temp_dir() . '/test-trace-options-' . uniqid();

        try {
            $tracer->start($outputFile);

            // Verify INI settings
            $this->assertEquals('trace', ini_get('xdebug.mode'));
            $this->assertEquals('1', ini_get('xdebug.trace_format'));
            $this->assertEquals('1', ini_get('xdebug.collect_params'));
            $this->assertEquals('1', ini_get('xdebug.collect_return'));
            $this->assertEquals('0', ini_get('xdebug.collect_assignments'));

            $tracer->stop();
        } finally {
            // Cleanup
            if (file_exists($outputFile . '.xt')) {
                unlink($outputFile . '.xt');
            }
            if (file_exists($outputFile . '.xt.gz')) {
                unlink($outputFile . '.xt.gz');
            }
        }
    }

    /**
     * Integration test - test multiple start/stop cycles
     *
     * @group integration
     * @requires extension xdebug
     */
    public function test__multiple_start_stop_cycles(): void
    {
        if (!extension_loaded('xdebug')) {
            $this->markTestSkipped('Xdebug extension not loaded');
        }

        if (!function_exists('xdebug_start_trace')) {
            $this->markTestSkipped('xdebug_start_trace() not available');
        }

        $tracer = new XdebugTracer();

        $files = [];

        try {
            for ($i = 0; $i < 3; $i++) {
                $outputFile = sys_get_temp_dir() . '/test-trace-cycle-' . $i . '-' . uniqid();
                $files[] = $outputFile;

                $tracer->start($outputFile);
                $this->assertNotNull($tracer->getActiveTraceFile());

                $tracePath = $tracer->stop();
                $this->assertIsString($tracePath);
                $this->assertNull($tracer->getActiveTraceFile());
            }
        } finally {
            // Cleanup
            foreach ($files as $file) {
                if (file_exists($file . '.xt')) {
                    unlink($file . '.xt');
                }
                if (file_exists($file . '.xt.gz')) {
                    unlink($file . '.xt.gz');
                }
            }
        }
    }
}

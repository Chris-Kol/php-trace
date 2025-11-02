<?php

namespace PhpTrace\Tests\Unit;

use PhpTrace\TraceParser;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for TraceParser
 */
class TraceParserTest extends TestCase
{
    private TraceParser $parser;

    protected function setUp(): void
    {
        $this->parser = new TraceParser();
    }

    public function test_parse__returnsArrayWithMetaAndTrace(): void
    {
        $fixtureFile = __DIR__ . '/../Fixtures/sample-trace.xt';

        $result = $this->parser->parse($fixtureFile);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('meta', $result);
        $this->assertArrayHasKey('trace', $result);
    }

    public function test_parse__extractsMetadataFromHeader(): void
    {
        $fixtureFile = __DIR__ . '/../Fixtures/sample-trace.xt';

        $result = $this->parser->parse($fixtureFile);

        $this->assertArrayHasKey('total_time_ms', $result['meta']);
        $this->assertArrayHasKey('function_count', $result['meta']);
        $this->assertArrayHasKey('timestamp', $result['meta']);
        $this->assertArrayHasKey('php_version', $result['meta']);
    }

    public function test_parse__buildsHierarchicalCallTree(): void
    {
        $fixtureFile = __DIR__ . '/../Fixtures/sample-trace.xt';

        $result = $this->parser->parse($fixtureFile);

        $this->assertIsArray($result['trace']);
        $this->assertNotEmpty($result['trace']);

        // Check that the main function has children
        $mainFunction = $result['trace'][0];
        $this->assertArrayHasKey('children', $mainFunction);
        $this->assertNotEmpty($mainFunction['children']);
    }

    public function test_parse__filtersVendorDirectories(): void
    {
        $fixtureFile = __DIR__ . '/../Fixtures/sample-trace.xt';

        $result = $this->parser->parse($fixtureFile);

        // Our fixture doesn't have vendor calls, so all functions should be present
        $this->assertNotEmpty($result['trace']);

        // Verify no vendor paths are in the result
        $this->assertTraceDoesNotContainVendorPaths($result['trace']);
    }

    public function test_parse__calculatesExecutionTime(): void
    {
        $fixtureFile = __DIR__ . '/../Fixtures/sample-trace.xt';

        $result = $this->parser->parse($fixtureFile);

        foreach ($result['trace'] as $call) {
            $this->assertArrayHasKey('duration_ms', $call);
            if ($call['duration_ms'] !== null) {
                $this->assertIsFloat($call['duration_ms']);
                $this->assertGreaterThanOrEqual(0, $call['duration_ms']);
            }
        }
    }

    public function test_parse__identifiesSlowFunctions(): void
    {
        $fixtureFile = __DIR__ . '/../Fixtures/sample-trace.xt';

        $result = $this->parser->parse($fixtureFile);

        // Our fixture has a slowFunction that takes >100ms
        $slowFunctionFound = false;

        foreach ($result['trace'] as $call) {
            if (isset($call['children'])) {
                foreach ($call['children'] as $child) {
                    $isSlowFunction = $child['function'] === 'slowFunction'
                        && isset($child['duration_ms'])
                        && $child['duration_ms'] > 100;

                    if ($isSlowFunction) {
                        $slowFunctionFound = true;
                        break 2;
                    }
                }
            }
        }

        $this->assertTrue($slowFunctionFound, 'Expected to find slowFunction with >100ms execution time');
    }

    public function test__setExcludePatterns__replacesExistingPatterns(): void
    {
        $parser = new TraceParser(['initial/']);

        $parser->setExcludePatterns(['new/', 'patterns/']);

        // Create a trace file with different patterns
        $tempFile = sys_get_temp_dir() . '/test-trace-' . uniqid() . '.xt';
        file_put_contents($tempFile, <<<'TRACE'
Version: 3.1.0
TRACE_START
0	1	0	0.000100	100000	testFunc	1		/project/new/file.php	5
0	1	1	0.000200	100000
0	2	0	0.000300	100000	testFunc2	1		/project/patterns/file.php	10
0	2	1	0.000400	100000
0	3	0	0.000500	100000	keepFunc	1		/project/src/file.php	15
0	3	1	0.000600	100000
TRACE_END
TRACE
        );

        $result = $parser->parse($tempFile);

        // Should only have keepFunc (new/ and patterns/ are excluded)
        $this->assertCount(1, $result['trace']);
        $this->assertEquals('keepFunc', $result['trace'][0]['function']);

        unlink($tempFile);
    }

    public function test__addExcludePattern__addsToExistingPatterns(): void
    {
        $parser = new TraceParser(['vendor/']);

        $parser->addExcludePattern('custom/');

        // Create a trace file with both patterns
        $tempFile = sys_get_temp_dir() . '/test-trace-' . uniqid() . '.xt';
        file_put_contents($tempFile, <<<'TRACE'
Version: 3.1.0
TRACE_START
0	1	0	0.000100	100000	vendorFunc	1		/project/vendor/file.php	5
0	1	1	0.000200	100000
0	2	0	0.000300	100000	customFunc	1		/project/custom/file.php	10
0	2	1	0.000400	100000
0	3	0	0.000500	100000	keepFunc	1		/project/src/file.php	15
0	3	1	0.000600	100000
TRACE_END
TRACE
        );

        $result = $parser->parse($tempFile);

        // Should only have keepFunc (both vendor/ and custom/ are excluded)
        $this->assertCount(1, $result['trace']);
        $this->assertEquals('keepFunc', $result['trace'][0]['function']);

        unlink($tempFile);
    }

    public function test__parse__throwsExceptionWhenFileNotFound(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Trace file not found');

        $this->parser->parse('/nonexistent/file.xt');
    }

    public function test__parse__handlesEmptyFile(): void
    {
        $tempFile = sys_get_temp_dir() . '/empty-trace-' . uniqid() . '.xt';
        file_put_contents($tempFile, '');

        $result = $this->parser->parse($tempFile);

        $this->assertArrayHasKey('meta', $result);
        $this->assertArrayHasKey('trace', $result);
        $this->assertEquals(0, $result['meta']['total_time_ms']);
        $this->assertEquals(0, $result['meta']['function_count']);
        $this->assertEmpty($result['trace']);

        unlink($tempFile);
    }

    /**
     * Recursively check that trace does not contain vendor paths
     */
    private function assertTraceDoesNotContainVendorPaths(array $trace): void
    {
        foreach ($trace as $call) {
            if (isset($call['file'])) {
                $this->assertStringNotContainsString('vendor/', $call['file']);
                $this->assertStringNotContainsString('composer/', $call['file']);
            }

            if (isset($call['children']) && !empty($call['children'])) {
                $this->assertTraceDoesNotContainVendorPaths($call['children']);
            }
        }
    }
}

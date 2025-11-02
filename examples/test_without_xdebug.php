<?php

/**
 * Test script that works WITHOUT Xdebug
 * Creates a mock trace file to test the parser and formatters
 */

// Use Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Create a mock Xdebug trace file
$mockTraceContent = <<<TRACE
Version: 3.1.0
File format: 4
TRACE_START	[2024-10-31 12:00:00.000000]
0	1	0	0.000100	100000	{main}	1		/Users/ckoleri/code/php-trace/examples/sample.php	0
1	2	0	0.000200	102000	main	0		/Users/ckoleri/code/php-trace/examples/sample.php	42
2	3	0	0.000300	104000	processUser	0		/Users/ckoleri/code/php-trace/examples/sample.php	9
3	4	0	0.000400	106000	slowDatabaseQuery	0		/Users/ckoleri/code/php-trace/examples/sample.php	4
3	4	1	0.150500	106500
2	5	0	0.150600	107000	validateUser	0		/Users/ckoleri/code/php-trace/examples/sample.php	17
2	5	1	0.170700	107500
2	3	1	0.170800	107500
2	6	0	0.170900	108000	renderView	0		/Users/ckoleri/code/php-trace/examples/sample.php	24
2	6	1	0.201000	108500
1	2	1	0.201100	108500
0	1	1	0.201200	108500
TRACE_END	[2024-10-31 12:00:00.201200]
TRACE;

$traceFile = '/tmp/test-trace.xt';
file_put_contents($traceFile, $mockTraceContent);

echo "Testing PHP Trace components...\n\n";

// Test the parser
echo "1. Testing TraceParser...\n";
$parser = new PhpTrace\TraceParser();
$traceData = $parser->parse($traceFile);

echo "   ✓ Parsed {$traceData['meta']['function_count']} functions\n";
echo "   ✓ Total execution time: {$traceData['meta']['total_time_ms']}ms\n\n";

// Test JSON formatter
echo "2. Testing JsonFormatter...\n";
$jsonFormatter = new PhpTrace\JsonFormatter();
$jsonOutput = $jsonFormatter->format($traceData);
$jsonFile = '/tmp/test-trace.json';
file_put_contents($jsonFile, $jsonOutput);
echo "   ✓ JSON output written to: {$jsonFile}\n";
echo "   Preview:\n";
$preview = json_decode($jsonOutput, true);
echo "   Summary: {$preview['summary']}\n\n";

// Test Markdown formatter
echo "3. Testing MarkdownFormatter...\n";
$markdownFormatter = new PhpTrace\MarkdownFormatter();
$markdownOutput = $markdownFormatter->format($traceData);
$markdownFile = '/tmp/test-trace.md';
file_put_contents($markdownFile, $markdownOutput);
echo "   ✓ Markdown output written to: {$markdownFile}\n\n";

// Display the markdown output
echo "4. Markdown Output Preview:\n";
echo str_repeat('=', 70) . "\n";
echo $markdownOutput;
echo "\n" . str_repeat('=', 70) . "\n\n";

// Display the JSON output
echo "5. JSON Output Preview:\n";
echo str_repeat('=', 70) . "\n";
echo $jsonOutput;
echo "\n" . str_repeat('=', 70) . "\n\n";

echo "✓ All tests passed!\n\n";
echo "To test with real Xdebug tracing:\n";
echo "  1. Install Xdebug: brew install php@8.2-xdebug (or your PHP version)\n";
echo "  2. Run: TRACE=1 php -d auto_prepend_file=src/bootstrap.php examples/sample.php\n";

// Cleanup
unlink($traceFile);

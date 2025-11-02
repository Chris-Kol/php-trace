<?php

/**
 * PHP Trace Bootstrap
 *
 * This file can be used as an auto_prepend_file to automatically
 * enable tracing when the TRACE environment variable is set.
 *
 * Usage:
 *   TRACE=1 php your-script.php
 *
 * Or add to php.ini:
 *   auto_prepend_file=/path/to/php-trace/src/bootstrap.php
 *
 * Or use with PHP built-in server:
 *   php -S localhost:8000 -d auto_prepend_file=vendor/php-trace/php-trace/src/bootstrap.php
 */

// Autoload classes
$autoloadPaths = [
    __DIR__ . '/../vendor/autoload.php',  // Installed via Composer
    __DIR__ . '/../../autoload.php',      // Installed as dependency
];

foreach ($autoloadPaths as $autoload) {
    if (file_exists($autoload)) {
        require_once $autoload;
        break;
    }
}

// Check if classes are available (fallback for non-Composer installations)
if (!class_exists('PhpTrace\TraceManager')) {
    // Manual class loading for standalone usage
    $classFiles = [
        __DIR__ . '/Config/TraceConfig.php',
        __DIR__ . '/Detector/DetectorInterface.php',
        __DIR__ . '/Detector/EnvDetector.php',
        __DIR__ . '/Detector/DotEnvDetector.php',
        __DIR__ . '/Detector/QueryDetector.php',
        __DIR__ . '/Detector/CookieDetector.php',
        __DIR__ . '/Detector/CompositeDetector.php',
        __DIR__ . '/Tracer/TracerInterface.php',
        __DIR__ . '/Tracer/XdebugTracer.php',
        __DIR__ . '/Parser/ParserInterface.php',
        __DIR__ . '/TraceParser.php',
        __DIR__ . '/Formatter/FormatterInterface.php',
        __DIR__ . '/JsonFormatter.php',
        __DIR__ . '/MarkdownFormatter.php',
        __DIR__ . '/Writer/WriterInterface.php',
        __DIR__ . '/Writer/FileWriter.php',
        __DIR__ . '/TraceManager.php',
    ];

    foreach ($classFiles as $file) {
        if (file_exists($file)) {
            require_once $file;
        }
    }
}

try {
    // Load DI container (with fallback for manual instantiation)
    if (file_exists(__DIR__ . '/container.php')) {
        $container = require __DIR__ . '/container.php';
        $manager = $container->get(PhpTrace\TraceManager::class);
    } else {
        // Fallback: Manual instantiation without DI container
        $config = PhpTrace\Config\TraceConfig::load();

        $detector = new PhpTrace\Detector\CompositeDetector([
            new PhpTrace\Detector\EnvDetector(),
            new PhpTrace\Detector\DotEnvDetector(),
            new PhpTrace\Detector\QueryDetector($_GET),
            new PhpTrace\Detector\CookieDetector($_COOKIE),
        ]);

        $tracer = new PhpTrace\Tracer\XdebugTracer(
            collectParams: false,
            collectReturn: false,
            collectAssignments: false
        );

        $parser = new PhpTrace\TraceParser($config->getExcludePatterns());

        $formatters = [
            new PhpTrace\JsonFormatter(),
            new PhpTrace\MarkdownFormatter(),
        ];

        $writer = new PhpTrace\Writer\FileWriter(
            createDirectories: true,
            directoryPermissions: 0755
        );

        $manager = new PhpTrace\TraceManager(
            $detector,
            $tracer,
            $parser,
            $formatters,
            $writer,
            $config
        );
    }

    // Execute tracing
    $manager->execute();
} catch (\Throwable $e) {
    // Silent failure - don't break the application if tracing fails
    error_log('[PHP-Trace] Bootstrap error: ' . $e->getMessage());
}

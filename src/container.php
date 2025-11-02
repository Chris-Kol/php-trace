<?php

use DI\ContainerBuilder;
use function DI\autowire;
use function DI\get;
use PhpTrace\Config\TraceConfig;
use PhpTrace\Detector\CompositeDetector;
use PhpTrace\Detector\CookieDetector;
use PhpTrace\Detector\DetectorInterface;
use PhpTrace\Detector\DotEnvDetector;
use PhpTrace\Detector\EnvDetector;
use PhpTrace\Detector\QueryDetector;
use PhpTrace\Formatter\FormatterInterface;
use PhpTrace\JsonFormatter;
use PhpTrace\MarkdownFormatter;
use PhpTrace\Parser\ParserInterface;
use PhpTrace\TraceParser;
use PhpTrace\Tracer\TracerInterface;
use PhpTrace\Tracer\XdebugTracer;
use PhpTrace\Writer\FileWriter;
use PhpTrace\Writer\WriterInterface;

// Build the container
$containerBuilder = new ContainerBuilder();

$containerBuilder->addDefinitions([
    // Configuration
    TraceConfig::class => function () {
        return TraceConfig::load();
    },

    // Detector (Composite pattern combining all detection methods)
    DetectorInterface::class => function () {
        return new CompositeDetector([
            new EnvDetector(),
            new DotEnvDetector(),
            new QueryDetector($_GET),
            new CookieDetector($_COOKIE),
        ]);
    },

    // Tracer
    TracerInterface::class => function () {
        return new XdebugTracer(
            collectParams: false,
            collectReturn: false,
            collectAssignments: false
        );
    },

    // Parser
    ParserInterface::class => function (TraceConfig $config) {
        return new TraceParser($config->getExcludePatterns());
    },

    // Formatters (array of all formatters)
    'formatters' => function () {
        return [
            new JsonFormatter(),
            new MarkdownFormatter(),
        ];
    },

    // Writer
    WriterInterface::class => function () {
        return new FileWriter(
            createDirectories: true,
            directoryPermissions: 0755
        );
    },

    // TraceManager (autowire with explicit formatters parameter)
    \PhpTrace\TraceManager::class => autowire()
        ->constructorParameter('formatters', get('formatters')),
]);

return $containerBuilder->build();

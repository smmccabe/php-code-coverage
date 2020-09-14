<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage\Report;

use SebastianBergmann\CodeCoverage\CodeCoverage;
use SebastianBergmann\CodeCoverage\Node\File;
use SebastianBergmann\CodeCoverage\RuntimeException;

/**
 * @internal This class is not covered by the backward compatibility promise for phpunit/php-code-coverage
 */
final class Cobertura
{
    /**
     * @throws WriteOperationFailedException
     */
    public function process(CodeCoverage $coverage, ?string $target = null, ?string $name = null): string
    {
        $time = (string) $_SERVER['REQUEST_TIME'];
        $cwd = getcwd();

        $xmlDocument               = new \DOMDocument('1.0', 'UTF-8');
        $xmlDocument->formatOutput = true;

        $xmlCoverage = $xmlDocument->createElement('coverage');
        $xmlCoverage->setAttribute('line-rate', '');
        $xmlCoverage->setAttribute('branch-rate', '');
        $xmlCoverage->setAttribute('lines-covered', '');
        $xmlCoverage->setAttribute('lines-valid', '');
        $xmlCoverage->setAttribute('branches-covered', '');
        $xmlCoverage->setAttribute('branches-valid', '');
        $xmlCoverage->setAttribute('complexity', '');
        $xmlCoverage->setAttribute('version', '0.4');
        $xmlCoverage->setAttribute('timestamp', $time);
        $xmlDocument->appendChild($xmlCoverage);

        $xmlSources = $xmlDocument->createElement('sources');
        $xmlCoverage->appendChild($xmlSources);

        $xmlSource = $xmlDocument->createElement('source');
        $xmlSource->nodeValue = $cwd;
        $xmlSources->appendChild($xmlSource);

        $xmlPackages = $xmlDocument->createElement('packages');
        $xmlCoverage->appendChild($xmlPackages);

        $xmlPackage = $xmlDocument->createElement('package');
        $xmlPackages->appendChild($xmlPackage);

        $report   = $coverage->getReport();

        foreach ($report as $item) {
            if (!$item instanceof File) {
                continue;
            }

            $classes      = $item->getClassesAndTraits();
            $coverageData = $item->getCoverageData();

            foreach ($classes as $className => $class) {
                $xmlClass = $xmlDocument->createElement('class');
                $xmlClass->setAttribute('name', $className);
                $xmlClass->setAttribute('filename', str_replace($cwd . '/', '', $item->getPath()));
                $xmlClass->setAttribute('line-rate', '');
                $xmlClass->setAttribute('branch-rate', '');
                $xmlClass->setAttribute('complexity', (string) $class['ccn']);
                $xmlPackage->appendChild($xmlClass);

                $xmlMethods = $xmlDocument->createElement('methods');
                $xmlClass->appendChild($xmlMethods);

                $xmlClassLines = $xmlDocument->createElement('lines');
                $xmlClass->appendChild($xmlClassLines);

                foreach ($class['methods'] as $methodName => $method) {
                    if ($method['executableLines'] == 0) {
                        continue;
                    }

                    $methodCount = 0;

                    foreach (range($method['startLine'], $method['endLine']) as $line) {
                        if (isset($coverageData[$line]) && ($coverageData[$line] !== null)) {

                            $methodCount = max($methodCount, count($coverageData[$line]));

                            $xmlClassLine = $xmlDocument->createElement('line');
                            $xmlClassLine->setAttribute('number', (string) $line);
                            $xmlClassLine->setAttribute('hits', (string) count($coverageData[$line]));
                            $xmlClassLines->appendChild($xmlClassLine);
                        }
                    }

                    $xmlMethod = $xmlDocument->createElement('method');
                    $xmlMethod->setAttribute('name', $methodName);
                    $xmlMethod->setAttribute('signature', '');
                    $xmlMethod->setAttribute('line-rate', '');
                    $xmlMethod->setAttribute('branch-rate', '');
                    $xmlMethod->setAttribute('complexity', (string) $method['ccn']);

                    $xmlLines = $xmlDocument->createElement('lines');
                    $xmlMethod->appendChild($xmlLines);

                    $xmlLine = $xmlDocument->createElement('line');
                    $xmlLine->setAttribute('number', (string) $method['startLine']);
                    $xmlLine->setAttribute('hits', (string) $methodCount);
                    $xmlLines->appendChild($xmlLine);

                    $xmlMethods->appendChild($xmlMethod);
                }
            }
        }

        $buffer = $xmlDocument->saveXML();

        if ($target !== null) {
            if (!$this->createDirectory(\dirname($target))) {
                throw new \RuntimeException(\sprintf('Directory "%s" was not created', \dirname($target)));
            }

            if (@\file_put_contents($target, $buffer) === false) {
                throw new RuntimeException(
                    \sprintf(
                        'Could not write to "%s',
                        $target
                    )
                );
            }
        }

        return $buffer;
    }

    private function createDirectory(string $directory): bool
    {
        return !(!\is_dir($directory) && !@\mkdir($directory, 0777, true) && !\is_dir($directory));
    }
}

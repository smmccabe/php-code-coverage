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

use SebastianBergmann\CodeCoverage\TestCase;

/**
 * @covers \SebastianBergmann\CodeCoverage\Report\Cobertura
 */
final class CoberturaTest extends TestCase
{
    public function testPathCoverageForBankAccountTest()
    {
        $cobertura = new Cobertura;

        $this->assertStringMatchesFormatFile(
            TEST_FILES_PATH . 'BankAccount-cobertura.xml',
            $cobertura->process($this->getCoverageForBankAccount(), null, 'BankAccount')
        );
    }

    public function testCoberturaForFileWithIgnoredLines()
    {
        $cobertura = new Cobertura;

        $this->assertStringMatchesFormatFile(
            TEST_FILES_PATH . 'ignored-lines-cobertura.xml',
            $cobertura->process($this->getCoverageForFileWithIgnoredLines())
        );
    }

    public function testCoberturaForClassWithAnonymousFunction()
    {
        $cobertura = new Cobertura;

        $this->assertStringMatchesFormatFile(
            TEST_FILES_PATH . 'class-with-anonymous-function-cobertura.xml',
            $cobertura->process($this->getCoverageForClassWithAnonymousFunction())
        );
    }
}

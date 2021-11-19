<?php

declare(strict_types=1);

namespace Lullabot\DrainpipeDev\Tests\Functional;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

class InstallerScaffoldTest extends TestCase
{
    public const PROJECT_PATH = __DIR__.'/../../fixtures/drainpipe-test-project';

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        $composer = new Process(['composer', 'update'], self::PROJECT_PATH);
        $composer->run();
    }

    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();

        if (file_exists(self::PROJECT_PATH.'/.gitignore')) {
            unlink(self::PROJECT_PATH.'/.gitignore');
        }
        $clean = new Process(['git', 'clean', '-fdX'], self::PROJECT_PATH);
        $clean->run();
    }

    public function testBinaries(): void
    {
        $this->assertFileExists(self::PROJECT_PATH.'/vendor/bin/local-php-security-checker');
    }
}

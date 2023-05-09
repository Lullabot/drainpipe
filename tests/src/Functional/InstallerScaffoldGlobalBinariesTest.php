<?php

declare(strict_types=1);

namespace Lullabot\Drainpipe\Tests\Functional;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

class InstallerScaffoldGlobalBinariesTest extends TestCase
{
    public const PROJECT_PATH = __DIR__.'/../../fixtures/drainpipe-test-project-global-binaries';

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

    public function testTaskfile(): void
    {
        $this->assertEquals(sha1_file(self::PROJECT_PATH.'/vendor/lullabot/drainpipe/scaffold/Taskfile.yml'), sha1_file(self::PROJECT_PATH.'/Taskfile.yml'));
    }

    public function testGitIgnore(): void
    {
        $gitignore = file_get_contents(self::PROJECT_PATH.'/.gitignore');
        $this->assertStringContainsString('.task', $gitignore);
    }

    public function testBinaries(): void
    {
        $this->assertFileDoesNotExist(self::PROJECT_PATH.'/vendor/bin/task');
        $this->assertFileDoesNotExist(self::PROJECT_PATH.'/vendor/bin/local-php-security-checker');
    }
}

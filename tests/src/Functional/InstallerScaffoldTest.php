<?php

declare(strict_types=1);

namespace Lullabot\Drainpipe\Tests\Functional;

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

    public function testTaskfile(): void
    {
        $this->assertEquals(sha1_file(self::PROJECT_PATH.'/vendor/lullabot/drainpipe/scaffold/Taskfile.yml'), sha1_file(self::PROJECT_PATH.'/Taskfile.yml'));
    }

    public function testGitIgnore(): void
    {
        $gitignore = file_get_contents(self::PROJECT_PATH.'/.gitignore');
        $this->assertStringContainsString('.task', $gitignore);
    }

    public function testDockerfile(): void {
        if (!getenv('IS_DDEV_PROJECT')) {
            $this->markTestSkipped('Not running with DDEV');
        }
        $this->assertFileExists(self::PROJECT_PATH.'/.ddev/web-build/Dockerfile.drainpipe');
    }

    public function testBinaries(): void
    {
        if (!getenv('IS_DDEV_PROJECT')) {
            $this->markTestSkipped('Not running with DDEV');
        }
        $this->assertFileExists('/usr/local/bin/task');
    }
}

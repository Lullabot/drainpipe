<?php

declare(strict_types=1);

namespace Lullabot\Drainpipe\Tests\Unit;

use Composer\Composer;
use Composer\IO\BufferIO;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;
use Lullabot\Drainpipe\ProtectedWebPaths;
use Lullabot\Drainpipe\ScaffoldInstallerPlugin;
use PHPUnit\Framework\TestCase;

class ScaffoldInstallerPluginProtectWebPathsTest extends TestCase
{
    private const HTACCESS = "<IfModule mod_rewrite.c>\n  RewriteEngine on\n</IfModule>\n";

    private string $tmpDir;

    private string $originalCwd;

    private BufferIO $io;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalCwd = getcwd();
        $this->tmpDir = sys_get_temp_dir() . '/drainpipe-protect-' . bin2hex(random_bytes(4));
        mkdir($this->tmpDir);
        chdir($this->tmpDir);
        $this->io = new BufferIO();
    }

    protected function tearDown(): void
    {
        chdir($this->originalCwd);
        exec('rm -rf ' . escapeshellarg($this->tmpDir));
        parent::tearDown();
    }

    private function writeHtaccess(string $webRoot, string $contents = self::HTACCESS): string
    {
        mkdir($webRoot, 0777, true);
        file_put_contents("$webRoot/.htaccess", $contents);
        return "$webRoot/.htaccess";
    }

    private function protect(array $extra): void
    {
        $plugin = new ScaffoldInstallerPlugin();
        foreach (['io' => $this->io, 'extra' => $extra] as $name => $value) {
            $property = new \ReflectionProperty(ScaffoldInstallerPlugin::class, $name);
            $property->setAccessible(true);
            $property->setValue($plugin, $value);
        }
        $plugin->protectWebPaths(new Event(ScriptEvents::POST_INSTALL_CMD, new Composer(), $this->io));
    }

    public function testRunsAfterCoreScaffold(): void
    {
        $events = ScaffoldInstallerPlugin::getSubscribedEvents();
        foreach ([ScriptEvents::POST_INSTALL_CMD, ScriptEvents::POST_UPDATE_CMD] as $event) {
            $this->assertContains(['protectWebPaths', -100], $events[$event]);
        }
    }

    public function testNotAcquiaLeavesHtaccessAlone(): void
    {
        $path = $this->writeHtaccess('web');
        $this->protect(['drainpipe' => ['github' => ['pantheon' => ['Actions']]]]);
        $this->assertSame(self::HTACCESS, file_get_contents($path));
    }

    /**
     * @dataProvider acquiaConfigProvider
     */
    public function testAcquiaConfigAddsBlock(array $drainpipe): void
    {
        $path = $this->writeHtaccess('web');
        $this->protect(['drainpipe' => $drainpipe]);
        $this->assertStringStartsWith(ProtectedWebPaths::HTACCESS_BEGIN, file_get_contents($path));
    }

    public static function acquiaConfigProvider(): array
    {
        return [
            'acquia key' => [['acquia' => ['settings' => true]]],
            'github actions' => [['github' => ['acquia' => ['Deploy']]]],
            'bitbucket pipeline' => [['bitbucket' => ['AcquiaDeploy']]],
        ];
    }

    public function testUsesScaffoldWebRoot(): void
    {
        $path = $this->writeHtaccess('docroot');
        $this->protect([
            'drupal-scaffold' => ['locations' => ['web-root' => 'docroot/']],
            'drainpipe' => ['acquia' => []],
        ]);
        $this->assertStringStartsWith(ProtectedWebPaths::HTACCESS_BEGIN, file_get_contents($path));
    }

    public function testOptOutRemovesBlock(): void
    {
        $path = $this->writeHtaccess('web', ProtectedWebPaths::applyToHtaccess(self::HTACCESS));
        $this->protect(['drainpipe' => ['acquia' => ['protected-web-paths' => false]]]);
        $this->assertSame(self::HTACCESS, file_get_contents($path));
    }

    public function testMissingHtaccessWarns(): void
    {
        $this->protect(['drainpipe' => ['acquia' => []]]);
        $this->assertStringContainsString('not found', $this->io->getOutput());
    }
}

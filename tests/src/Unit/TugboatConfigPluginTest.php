<?php

declare(strict_types=1);

namespace Lullabot\Drainpipe\Tests\Unit;

use Composer\Config;
use Composer\IO\BufferIO;
use Composer\IO\NullIO;
use Lullabot\Drainpipe\TugboatConfigPlugin;
use PHPUnit\Framework\TestCase;

class TugboatConfigPluginTest extends TestCase
{
    private ?string $tmpDir = null;

    private ?string $originalCwd = null;

    protected function tearDown(): void
    {
        if ($this->originalCwd !== null) {
            chdir($this->originalCwd);
        }
        if ($this->tmpDir !== null) {
            $this->removeDir($this->tmpDir);
        }
        parent::tearDown();
    }

    private function removeDir(string $dir): void
    {
        foreach (scandir($dir) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = "$dir/$item";
            if (is_dir($path) && !is_link($path)) {
                $this->removeDir($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }

    private function createPlugin(array $extra, $io = null): TugboatConfigPlugin
    {
        $plugin = new TugboatConfigPlugin();
        $values = [
            'io' => $io ?? new NullIO(),
            'extra' => $extra,
        ];
        if ($this->tmpDir !== null) {
            $config = new Config(false, $this->tmpDir);
            $config->merge(['config' => ['vendor-dir' => $this->tmpDir . '/vendor']]);
            $values['config'] = $config;
        }
        foreach ($values as $name => $value) {
            $property = new \ReflectionProperty(TugboatConfigPlugin::class, $name);
            $property->setAccessible(true);
            $property->setValue($plugin, $value);
        }
        return $plugin;
    }

    private function invoke(TugboatConfigPlugin $plugin, string $method, array $args = [])
    {
        $reflection = new \ReflectionMethod(TugboatConfigPlugin::class, $method);
        $reflection->setAccessible(true);
        return $reflection->invokeArgs($plugin, $args);
    }

    /**
     * Create a temporary project directory and chdir into it.
     */
    private function createProject(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/drainpipe-tugboat-' . bin2hex(random_bytes(6));
        mkdir($this->tmpDir . '/vendor/lullabot', 0777, true);
        // The plugin loads templates from vendor/lullabot/drainpipe.
        symlink(dirname(__DIR__, 3), $this->tmpDir . '/vendor/lullabot/drainpipe');
        $this->originalCwd = getcwd();
        chdir($this->tmpDir);
    }

    /**
     * @dataProvider webRootProvider
     */
    public function testGetWebRoot(array $extra, string $expected): void
    {
        $plugin = $this->createPlugin($extra);
        $this->assertSame($expected, $this->invoke($plugin, 'getWebRoot'));
    }

    public static function webRootProvider(): array
    {
        $scaffold = fn ($webRoot) => ['drupal-scaffold' => ['locations' => ['web-root' => $webRoot]]];
        return [
            'default' => [[], 'web'],
            'web with slash' => [$scaffold('web/'), 'web'],
            'docroot' => [$scaffold('docroot/'), 'docroot'],
            'docroot with dot slash' => [$scaffold('./docroot/'), 'docroot'],
            'nested' => [$scaffold('app/web/'), 'app/web'],
            'non-string' => [$scaffold(['invalid']), 'web'],
        ];
    }

    public function testSettingsFileWrittenToConfiguredWebRoot(): void
    {
        $this->createProject();
        mkdir('docroot/sites/default', 0777, true);
        file_put_contents('docroot/sites/default/settings.php', "<?php\n");

        $plugin = $this->createPlugin(['drupal-scaffold' => ['locations' => ['web-root' => 'docroot/']]]);
        $result = $this->invoke($plugin, 'generateSettingsFile', [['database' => ['type' => 'mariadb']]]);

        $this->assertTrue($result);
        $this->assertFileExists('docroot/sites/default/settings.tugboat.php');
        $this->assertStringContainsString(
            'include __DIR__ . "/settings.tugboat.php";',
            file_get_contents('docroot/sites/default/settings.php')
        );
        $this->assertDirectoryDoesNotExist('web');
    }

    public function testSettingsFileFailsLoudlyWhenWebRootMissing(): void
    {
        $this->createProject();
        $io = new BufferIO();

        $plugin = $this->createPlugin([], $io);
        $result = $this->invoke($plugin, 'generateSettingsFile', [['database' => ['type' => 'mariadb']]]);

        $this->assertFalse($result);
        $this->assertStringContainsString('Unable to write ./web/sites/default/settings.tugboat.php', $io->getOutput());
    }

    public function testPhpInitTemplateUsesWebRoot(): void
    {
        $this->createProject();
        $plugin = $this->createPlugin([]);
        $twig = $this->invoke($plugin, 'initTwigLoader');

        $rendered = $twig->render('php-init.yml.twig', [
            'services' => ['database' => ['type' => 'mariadb']],
            'web_root' => 'docroot',
        ]);
        $this->assertStringContainsString('ln -snf "${TUGBOAT_ROOT}/docroot" "${DOCROOT}"', $rendered);

        $rendered = $twig->render('php-init.yml.twig', [
            'services' => ['database' => ['type' => 'mariadb']],
        ]);
        $this->assertStringContainsString('ln -snf "${TUGBOAT_ROOT}/web" "${DOCROOT}"', $rendered);
    }

    private function extractVersionFromImage(string $image): string
    {
        $plugin = new TugboatConfigPlugin();
        $ioProperty = new \ReflectionProperty(TugboatConfigPlugin::class, 'io');
        $ioProperty->setAccessible(true);
        $ioProperty->setValue($plugin, new NullIO());
        $method = new \ReflectionMethod(TugboatConfigPlugin::class, 'extractVersionFromImage');
        $method->setAccessible(true);
        return $method->invoke($plugin, $image);
    }

    /**
     * @dataProvider imageProvider
     */
    public function testExtractImageVersion(string $imageRaw, string $expectedVersion): void
    {
        $version = $this->extractVersionFromImage($imageRaw);
        $this->assertSame($expectedVersion, $version);
    }

    public static function imageProvider(): array
    {
        return [
            // Simple image:tag formats
            ['redis:7-alpine', '7-alpine'],
            ['tugboatqa/redis:bookworm', 'bookworm'],
            ['solr:9.4', '9.4'],
            // ${VAR:-image:tag} formats
            ['${REDIS_DOCKER_IMAGE:-redis:7}', '7'],
            ['${SOLR_BASE_IMAGE:-solr:9.4}', '9.4'],
            ['${SOLR_BASE_IMAGE:-solr:9}', '9'],
            // image:${TAG:-version} format
            ['redis:${REDIS_TAG:-6-bullseye}', '6-bullseye'],
            // DDEV generated built-image pattern: ${VAR:-image:tag}-${DDEV_SITENAME}-built
            ['${SOLR_BASE_IMAGE:-solr:9.4}-${DDEV_SITENAME}-built', '9.4'],
            ['${SOLR_BASE_IMAGE:-solr:9}-${DDEV_SITENAME}-built', '9'],
            ['${SOLR_BASE_IMAGE:-solr:latest}-${DDEV_SITENAME}-built', 'latest'],
        ];
    }

    public function testExtractImageVersionFallsBackToLatest(): void
    {
        // Images with no recognizable tag pattern fall back to 'latest'
        $version = $this->extractVersionFromImage('invalidimage');
        $this->assertSame('latest', $version);
    }
}

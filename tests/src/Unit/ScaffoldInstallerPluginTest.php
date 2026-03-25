<?php

declare(strict_types=1);

namespace Lullabot\Drainpipe\Tests\Unit;

use Lullabot\Drainpipe\TugboatConfigPlugin;
use PHPUnit\Framework\TestCase;

class ScaffoldInstallerPluginTest extends TestCase
{
    private function extractVersionFromImage(string $image): string
    {
        $plugin = new TugboatConfigPlugin();
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
}

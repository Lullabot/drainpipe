<?php

declare(strict_types=1);

namespace Lullabot\Drainpipe\Tests\Unit;

use Lullabot\Drainpipe\ScaffoldInstallerPlugin;
use PHPUnit\Framework\TestCase;

class ScaffoldInstallerPluginTest extends TestCase
{
    /**
     * @dataProvider imageProvider
     */
    public function testExtractRedisImageVersion(string $imageRaw, string $expectedVersion): void
    {
        $version = ScaffoldInstallerPlugin::extractRedisImageVersion($imageRaw);
        $this->assertSame($expectedVersion, $version);
    }

    public static function imageProvider(): array
    {
        return [
            ['${REDIS_DOCKER_IMAGE:-redis:7}', '7'],
            ['redis:${REDIS_TAG:-6-bullseye}', '6-bullseye'],
            ['redis:7-alpine', '7-alpine'],
            ['tugboatqa/redis:bookworm', 'bookworm'],
        ];
    }

    public function testInvalidImageThrows(): void
    {
        $this->expectException(\RuntimeException::class);
        ScaffoldInstallerPlugin::extractRedisImageVersion('invalidimage');
    }
}

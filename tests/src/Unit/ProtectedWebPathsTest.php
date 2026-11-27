<?php

declare(strict_types=1);

namespace Lullabot\Drainpipe\Tests\Unit;

use Lullabot\Drainpipe\ProtectedWebPaths;
use PHPUnit\Framework\TestCase;

class ProtectedWebPathsTest extends TestCase
{
    private const HTACCESS = "#\n# Apache/PHP/Drupal settings:\n#\n\n<IfModule mod_rewrite.c>\n  RewriteEngine on\n</IfModule>\n";

    public function testApplyPrependsBlock(): void
    {
        $result = ProtectedWebPaths::applyToHtaccess(self::HTACCESS);

        $this->assertStringStartsWith(ProtectedWebPaths::HTACCESS_BEGIN, $result);
        $this->assertStringEndsWith(self::HTACCESS, $result);
        $this->assertStringContainsString('RewriteRule ^(core/)?(install|update)\.php(/|$) - [F]', $result);
    }

    public function testApplyIsIdempotent(): void
    {
        $once = ProtectedWebPaths::applyToHtaccess(self::HTACCESS);
        $twice = ProtectedWebPaths::applyToHtaccess($once);

        $this->assertSame($once, $twice);
        $this->assertSame(1, substr_count($twice, ProtectedWebPaths::HTACCESS_BEGIN));
    }

    public function testApplyReplacesOutdatedBlock(): void
    {
        $outdated = ProtectedWebPaths::HTACCESS_BEGIN . "\n# old rules\n" . ProtectedWebPaths::HTACCESS_END . "\n\n" . self::HTACCESS;

        $this->assertSame(
            ProtectedWebPaths::applyToHtaccess(self::HTACCESS),
            ProtectedWebPaths::applyToHtaccess($outdated)
        );
    }

    public function testRemoveRestoresOriginal(): void
    {
        $applied = ProtectedWebPaths::applyToHtaccess(self::HTACCESS);

        $this->assertSame(self::HTACCESS, ProtectedWebPaths::removeFromHtaccess($applied));
        $this->assertSame(self::HTACCESS, ProtectedWebPaths::removeFromHtaccess(self::HTACCESS));
    }

    /**
     * @dataProvider rewritePatternProvider
     */
    public function testRewritePattern(string $path, bool $blocked): void
    {
        // Use the pattern from the generated block. Per-directory RewriteRule
        // patterns match the path without the leading slash.
        $this->assertSame(1, preg_match('/RewriteRule (\S+) - \[F\]/', ProtectedWebPaths::htaccessBlock(), $matches));
        $pattern = '#' . $matches[1] . '#';
        $this->assertSame($blocked, (bool) preg_match($pattern, ltrim($path, '/')));
    }

    public static function rewritePatternProvider(): array
    {
        return [
            ['/core/install.php', true],
            ['/install.php', true],
            ['/update.php', true],
            ['/update.php/selection', true],
            ['/core/install.php/', true],
            ['/index.php', false],
            ['/modules/contrib/foo/update.php', false],
            ['/update.phpx', false],
            ['/node/update.php', false],
        ];
    }

    public function testMissingPantheonPaths(): void
    {
        $this->assertSame(ProtectedWebPaths::PATHS, ProtectedWebPaths::missingPantheonPaths([]));
        $this->assertSame(['/install.php', '/update.php'], ProtectedWebPaths::missingPantheonPaths([
            'protected_web_paths' => ['/core/install.php', '/private'],
        ]));
        $this->assertSame([], ProtectedWebPaths::missingPantheonPaths([
            'protected_web_paths' => ProtectedWebPaths::PATHS,
        ]));
    }
}

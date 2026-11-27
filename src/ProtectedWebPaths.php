<?php

declare(strict_types=1);

namespace Lullabot\Drainpipe;

/**
 * Blocks web access to Drupal's installer and update scripts.
 *
 * @see https://www.drupal.org/docs/administering-a-drupal-site/security-in-drupal/securing-the-installer-installphp
 * @see https://github.com/Lullabot/drainpipe/issues/1392
 */
final class ProtectedWebPaths
{
    /**
     * Paths blocked from web access, relative to the Drupal web root.
     */
    public const PATHS = [
        '/core/install.php',
        '/install.php',
        '/update.php',
    ];

    public const HTACCESS_BEGIN = '# BEGIN Drainpipe protected web paths';

    public const HTACCESS_END = '# END Drainpipe protected web paths';

    /**
     * Returns the .htaccess block that denies the protected paths.
     *
     * <Location> is not allowed in .htaccess files, so mod_rewrite is used.
     * The rule also matches path info (e.g. /update.php/selection) so the
     * update.php batch and selection pages are blocked as well.
     */
    public static function htaccessBlock(): string
    {
        return implode("\n", [
            self::HTACCESS_BEGIN,
            '# Managed by lullabot/drainpipe. Deny web access to the Drupal installer',
            '# and update.php. Run database updates with `drush updatedb` instead.',
            '# Opt out with extra.drainpipe.acquia.protected-web-paths: false.',
            '<IfModule mod_rewrite.c>',
            '  RewriteEngine on',
            '  RewriteRule ^(core/)?(install|update)\.php(/|$) - [F]',
            '</IfModule>',
            self::HTACCESS_END,
        ]) . "\n";
    }

    /**
     * Adds or refreshes the protected paths block in .htaccess contents.
     *
     * The block is prepended so it runs before Drupal's front controller
     * rewrite, which would otherwise route /install.php to index.php.
     */
    public static function applyToHtaccess(string $contents): string
    {
        $contents = self::removeFromHtaccess($contents);
        return self::htaccessBlock() . "\n" . $contents;
    }

    /**
     * Removes the protected paths block from .htaccess contents, if present.
     */
    public static function removeFromHtaccess(string $contents): string
    {
        $pattern = '/' . preg_quote(self::HTACCESS_BEGIN, '/') . '.*?' . preg_quote(self::HTACCESS_END, '/') . '\n*/s';
        return (string) preg_replace($pattern, '', $contents);
    }

    /**
     * Returns protected paths missing from a parsed pantheon.yml.
     *
     * @param array<mixed> $pantheonYml
     *
     * @return string[]
     */
    public static function missingPantheonPaths(array $pantheonYml): array
    {
        $configured = $pantheonYml['protected_web_paths'] ?? [];
        if (!is_array($configured)) {
            $configured = [];
        }
        return array_values(array_diff(self::PATHS, $configured));
    }
}

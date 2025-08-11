<?php

namespace Lullabot\DrainpipeDev\Composer\Plugin;

class ScaffoldHandler {

    /**
     * Get all Nightwatch-related scaffold files.
     */
    public static function getNightwatchScaffoldFiles(): array {
        return [
            '[project-root]/nightwatch.conf.js' => [
                'source' => 'scaffold/nightwatch/nightwatch.conf.js',
                'overwrite' => false,
            ],
            '[project-root]/.ddev/docker-compose.selenium.yaml' => [
                'source' => 'scaffold/nightwatch/.ddev/docker-compose.selenium.yaml',
                'overwrite' => false,
            ],
            '[project-root]/test/nightwatch/example.nightwatch.js' => [
                'source' => 'scaffold/nightwatch/test/nightwatch/example.nightwatch.js',
                'overwrite' => false,
            ],
            '[project-root]/test/nightwatch/vrt/.gitignore' => [
                'source' => 'scaffold/nightwatch/vrt.gitignore',
                'overwrite' => false,
            ],
            '[web-root]/sites/chrome/settings.php' => [
                'source' => 'scaffold/nightwatch/chrome.settings.php',
                'overwrite' => false,
            ],
            '[web-root]/sites/firefox/settings.php' => [
                'source' => 'scaffold/nightwatch/firefox.settings.php',
                'overwrite' => false,
            ],
            '[web-root]/sites/sites.php' => [
                'source' => 'scaffold/nightwatch/sites.php',
                'overwrite' => false,
            ],
        ];
    }

    /**
     * Check if Nightwatch testing is enabled in project configuration.
     */
    public static function isNightwatchEnabled(array $projectExtra): bool {
        return isset($projectExtra['drainpipe']['testing']) &&
               is_array($projectExtra['drainpipe']['testing']) &&
               in_array('Nightwatch', $projectExtra['drainpipe']['testing']);
    }
}

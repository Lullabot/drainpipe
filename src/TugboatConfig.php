<?php

declare(strict_types=1);

namespace Lullabot\Drainpipe;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

final class TugboatConfig implements ProviderInterface
{

    /**
     * @var \Twig\Environment
     */
    private $twig;

    /**
     * @var string
     */
    private $phpVersion;

    public function __construct(string $phpVersion)
    {
        $loader = new FilesystemLoader(__DIR__.'/../scaffold/tugboat');
        $this->twig = new Environment($loader);
        $this->phpVersion = $phpVersion;
    }

    public function render(
        string $name,
        string $host = self::HOST_UNKNOWN,
        bool $downsync = false
    ): string {
        switch ($host) {
            case self::HOST_ACQUIA:
                return $this->renderAcquia($name, $downsync);
            case self::HOST_PANTHEON:
                return $this->renderPantheon($name, $downsync);
            default:
                return $this->renderUnknown($name, $host, $downsync);
        }
    }

    public function writeFile(
        string $name,
        string $path,
        string $host = self::HOST_UNKNOWN,
        bool $downsync = false
    ): void {
        $basename = basename($name, '.twig');
        file_put_contents($path . "/$basename", $this->render($name, $host, $downsync));
    }

    private function renderAcquia(string $name, bool $downsync): string
    {
        return $this->twig->render($name, [
            'php_version' => $this->phpVersion,
            'database_type' => 'mysql',
            'database_version' => '5.7',
            'memory_cache_type' => 'memcached',
            'memory_cache_version' => 1,
            'host' => 'acquia',
            'downsync' => $downsync,
        ]);
    }

    private function renderPantheon(string $name, bool $downsync): string
    {
        return $this->twig->render($name, [
            'php_version' => $this->phpVersion,
            'database_type' => 'mariadb',
            'database_version' => '10.6',
            'memory_cache_type' => 'redis',
            'memory_cache_version' => 7,
            'host' => 'pantheon',
            'downsync' => $downsync,
        ]);
    }

    private function renderUnknown(string $name, string $host, bool $downsync): string
    {
        return $this->twig->render($name, [
            'php_version' => $this->phpVersion,
            'database_type' => 'mariadb',
            'database_version' => '10.6',
            'host' => $host,
            'downsync' => $downsync,
        ]);
    }

}

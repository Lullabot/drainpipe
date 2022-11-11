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

    public function __construct()
    {
        $loader = new FilesystemLoader(__DIR__.'../scaffold/tugboat');
        $this->twig = new Environment($loader);
    }

    public function render(
        string $name,
        string $provider = self::PROVIDER_UNKNOWN
    ): string {
        switch ($provider) {
            case self::PROVIDER_ACQUIA:
                return $this->renderAcquia($name);
            case self::PROVIDER_PANTHEON:
                return $this->renderPantheon($name);
            default:
                return $this->renderUnknown($name);
        }
    }

    public function writeFile(
        string $name,
        string $path,
        string $provider = self::PROVIDER_UNKNOWN
    ): void {
        file_put_contents($path, $this->render($name, $provider));
    }

    private function renderAcquia(string $name): string
    {
        return $this->twig->render($name, [
            'database_type' => 'mysql',
            'database_version' => '5.7',
            'memory_cache_type' => 'memcached',
            'memory_cache_version' => 1,
        ]);
    }

    private function renderPantheon(string $name): string
    {
        return $this->twig->render($name, [
            'database_type' => 'mariadb',
            'database_version' => '10.6',
            'memory_cache_type' => 'redis',
            'memory_cache_version' => 7,
        ]);
    }

    private function renderUnknown(string $name): string
    {
        return $this->twig->render($name, [
            'database_type' => 'mariadb',
            'database_version' => '10.6',
        ]);
    }

}

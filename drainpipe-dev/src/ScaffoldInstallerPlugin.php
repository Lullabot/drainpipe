<?php

declare(strict_types=1);

namespace Lullabot\DrainpipeDev;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;
use Composer\Util\Filesystem;

class ScaffoldInstallerPlugin implements PluginInterface, EventSubscriberInterface
{
    /**
     * @var IOInterface
     */
    protected $io;

    /**
     * Composer instance configuration.
     *
     * @var Config
     */
    protected $config;

    /**
     * Composer extra field configuration.
     *
     * @var array
     */
    protected $extra;

    /**
     * {@inheritDoc}
     */
    public function activate(Composer $composer, IOInterface $io)
    {
        $this->io = $io;
        $this->config = $composer->getConfig();
        $this->extra = $composer->getPackage()->getExtra();
    }

    /**
     * {@inheritDoc}
     */
    public function deactivate(Composer $composer, IOInterface $io)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function uninstall(Composer $composer, IOInterface $io)
    {
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            ScriptEvents::POST_INSTALL_CMD => 'onPostInstallCmd',
            ScriptEvents::POST_UPDATE_CMD => 'onPostUpdateCmd',
        ];
    }

    /**
     * Handle post install command events.
     *
     * @param Event $event the event to handle
     */
    public function onPostInstallCmd(Event $event)
    {
        $this->installPhpCs();
    }

    /**
     * Handle post update command events.
     *
     * @param event $event The event to handle
     */
    public function onPostUpdateCmd(Event $event)
    {
        $this->installPhpCs();
    }

    /**
     * Install PHP CS files.
     *
     * @throws \RuntimeException If the docroot placeholder can not be replaced.
     */
    private function installPhpCs(): void
    {
        $fs = new Filesystem();
        $vendor = $this->config->get('vendor-dir');

        $web_root = '';
        foreach (['web', 'docroot'] as $dir) {
            if ($this->isWebRoot('./' . $dir)) {
                $web_root = $dir;
            }
        }

        if (!is_file('./phpcs.xml.dist')) {
            $fs->copy($vendor . '/lullabot/drainpipe-dev/scaffold/phpcs.xml.dist', './phpcs.xml.dist');
            $content = file_get_contents('./phpcs.xml.dist');
            $newContent = str_replace('{% DOCROOT %}', $web_root, $content);
            if (file_put_contents('./phpcs.xml.dist', $newContent) === false) {
                throw new RuntimeException("Failed to write to file ./phpcs.xml.dist");
            }
        }
    }

    /**
     * Guess if a given directory is web root.
     */
    private function isWebRoot(string $path): bool
    {
        return (
          is_dir($path) &&
          is_file($path . '/index.php') &&
          is_dir($path . '/core') &&
          is_dir($path . '/profiles') &&
          is_dir($path . '/sites') &&
          is_dir($path . '/modules') &&
          is_dir($path . '/themes')
        );
    }

}

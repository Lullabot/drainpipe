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
     * @var \Composer\Config
     */
    protected $config;

    /**
     * Web root directory.
     *
     * @var string
     */
    protected $webRoot;

    /**
     * {@inheritDoc}
     */
    public function activate(Composer $composer, IOInterface $io)
    {
        $this->io = $io;
        $this->config = $composer->getConfig();
        $this->webRoot = $this->isWebRoot('./docroot') ? 'docroot' : 'web';
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
        $this->installPhpUnit();
    }

    /**
     * Handle post update command events.
     *
     * @param Event $event The event to handle
     */
    public function onPostUpdateCmd(Event $event)
    {
        $this->installPhpCs();
        $this->installPhpUnit();
    }

    /**
     * Install PHP CS files.
     *
     * @throws \RuntimeException If the docroot placeholder can not be replaced.
     */
    private function installPhpCs(): void
    {
        $this->scaffoldTemplate('phpcs.xml.dist', '{% DOCROOT %}', $this->webRoot);
    }

    /**
     * Install PHP Unit files.
     *
     * @throws \RuntimeException If the docroot placeholder can not be replaced.
     */
    private function installPhpUnit(): void
    {
        $this->scaffoldTemplate('phpunit.xml', '{% DOCROOT %}', $this->webRoot);
        $this->scaffoldTemplate('phpunit-testtraits.xml', '{% DOCROOT %}', $this->webRoot);
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

    /**
     * Helper function to scaffold a template file and replace its placeholders.
     */
    private function scaffoldTemplate(string $file, string $placeholder, string $placeholderValue): void {
        $fs = new Filesystem();
        $vendor = $this->config->get('vendor-dir');
        $source = sprintf('%s/lullabot/drainpipe-dev/scaffold/%s', $vendor, $file);
        $target = sprintf('./%s', $file);

        if (!is_file($target)) {
            $fs->copy($source, $target);
            $content = file_get_contents($target);
            $newContent = str_replace($placeholder, $placeholderValue, $content);
            if (file_put_contents($target, $newContent) === false) {
                throw new RuntimeException(sprintf('Failed to write to file %s', $target));
            }
            $this->io->write(sprintf('<info>Creating initial %s file...</info>', $target));
        }
    }

}

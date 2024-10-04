<?php

declare(strict_types=1);

namespace Lullabot\Drainpipe;

use Composer\Composer;
use Composer\Config;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;

class ComposerChecksPlugin implements PluginInterface, EventSubscriberInterface
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
     * Composer configuration advice: Use local copies of patch files.
     *
     * @return void
     */
    private function checkComposerPatchesAreLocal()
    {

        // Opt-out to avoid this check.
        if ($this->extra['draipipe']['composer']['disable-local-patches-check'] ?? false) {
            return;
        }

        $patchesInComposer = $this->extra['patches'] ?? false;
        $patchesInExtraFile = $this->extra['patches-file'] ?? false;

        // Patches defined on a separate file.
        if ($patchesInExtraFile) {
            if (!file_exists($patchesInExtraFile)) {
                $this->io->warning("The patches file `$patchesInExtraFile` can't be read.");
                return;
            }

            $patchesJsonEncoded = file_get_contents($patchesInExtraFile);
            $patchesContent = json_decode($patchesJsonEncoded, true)['patches'] ?? [];

            if (json_last_error()) {
                $this->io->warning(
                    "The patches file `$patchesInExtraFile` can't be parsed. Message \""
                    . json_last_error_msg(). '"'
                );
                return;
            }

        } else if ($patchesInComposer) {
            $patchesContent = $patchesInComposer;
        }

        if (empty($patchesContent)) {
            return;
        }

        // Patches content is not a string.
        if (!is_array($patchesContent)) {
            $this->io->warning("The patches content can't be validated. Check your patches defined in Composer.");
            return;
        }

        // Collecting remote patches (if any).
        $remotePatches = [];
        foreach ($patchesContent as $projectName => $patches) {
            foreach ($patches as $patchName => $patchUri) {
                if (str_starts_with($patchUri, 'http')) {
                    $remotePatches[$projectName] = "$patchName | $patchUri";
                }
            }
        }

        if (!$remotePatches) {
            return;
        }

        // Collect the remote patches info.
        $patchesInfo = PHP_EOL;
        $count = 1;
        foreach ($remotePatches as $projectName => $remote_patch) {
            $patchesInfo.= "[$count] $projectName: $remote_patch " . PHP_EOL;
            $count++;
        }
        $patchesInfo = rtrim($patchesInfo, PHP_EOL);

        // Communicate the user.
        $msg = 'Use local copies of patch files. See';
        $link = 'https://architecture.lullabot.com/adr/20220429-composer-patch-files/';
        $this->io->warning("$msg $link $patchesInfo");
    }

    /**
     * Composer configuration advice: "composer-exit-on-patch-failure": true
     *
     * @return void
     */
    private function checkComposerBreaksIfPatchesDoNotApply()
    {

        // Opt-out to avoid this check.
        if ($this->extra['draipipe']['composer']['disable-exit-on-patch-failure-check'] ?? false) {
            return;
        }

        $composerExitsOnPatchFailure = $this->extra['composer-exit-on-patch-failure']
            ?? false;
        $composerExitsOnPatchFailureBool = is_bool($composerExitsOnPatchFailure);
        $condition1 = !$composerExitsOnPatchFailure ||
            !$composerExitsOnPatchFailureBool;
        $condition2 = $composerExitsOnPatchFailureBool
            && $composerExitsOnPatchFailure !== true;
        $warn = $condition1 && $condition2;

        if (!$warn) {
            return;
        }

        $msg = "Break Composer install if patches don't apply. See";
        $link = 'https://architecture.lullabot.com/adr/20220429-composer-exit-failure/';
        $this->io->warning("$msg $link");
    }

    /**
     *  Composer configuration advice: "patchLevel": {"drupal/core": "-p2"}
     *
     * @return void
     */
    private function checkDrupalCoreComposerPatchesLevel()
    {

        // Opt-out to avoid this check.
        if ($this->extra['draipipe']['composer']['disable-drupal-core-patches-level-check'] ?? false) {
            return;
        }

        $patchLevel = $this->extra['patchLevel']['drupal/core'] ?? false;
        $patchLevelIsString = is_string($patchLevel);
        $warn = (!$patchLevel || !$patchLevelIsString)
            || ($patchLevelIsString && $patchLevel != '-p2');

        if (!$warn) {
            return;
        }

        $msg = 'Configure Composer patches to use `-p2` as `patchLevel` for Drupal core. See';
        $link = 'https://architecture.lullabot.com/adr/20220429-composer-patchlevel/';
        $this->io->warning("$msg $link");
    }

    /**
     *  Composer configuration advice: "patches-file" is not set/used.
     *
     * @return void
     */
    private function checkPatchesStoredInComposerJson()
    {

        // Opt-out to avoid this check.
        if ($this->extra['draipipe']['composer']['disable-patches-file-check'] ?? false) {
            return;
        }

        if (!isset($this->extra['patches-file'])) {
            return;
        }

        $msg = 'Store Composer patches configuration in `composer.json`. See';
        $link = 'https://architecture.lullabot.com/adr/20220429-composer-patches-inline/';
        $this->io->warning("$msg $link");
    }

    /**
     * Handle post install command events.
     *
     * @param Event $event the event to handle
     */
    public function onPostInstallCmd(Event $event)
    {
        // Composer checks.
        $this->checkDrupalCoreComposerPatchesLevel();
        $this->checkComposerBreaksIfPatchesDoNotApply();
        $this->checkPatchesStoredInComposerJson();
        $this->checkComposerPatchesAreLocal();
    }

    /**
     * Handle post update command events.
     *
     * @param event $event The event to handle
     */
    public function onPostUpdateCmd(Event $event)
    {
        // Composer checks.
        $this->checkDrupalCoreComposerPatchesLevel();
        $this->checkComposerBreaksIfPatchesDoNotApply();
        $this->checkPatchesStoredInComposerJson();
        $this->checkComposerPatchesAreLocal();
    }

}

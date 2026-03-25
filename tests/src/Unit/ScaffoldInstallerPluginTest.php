<?php

declare(strict_types=1);

namespace Lullabot\Drainpipe\Tests\Unit;

use Composer\IO\NullIO;
use Lullabot\Drainpipe\ScaffoldInstallerPlugin;
use PHPUnit\Framework\TestCase;

class ScaffoldInstallerPluginTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Creates a ScaffoldInstallerPlugin wired with NullIO and the given extra.
     */
    private function createPlugin(array $extra): ScaffoldInstallerPlugin
    {
        $plugin = new ScaffoldInstallerPlugin();

        $ioRef = new \ReflectionProperty(ScaffoldInstallerPlugin::class, 'io');
        $ioRef->setAccessible(true);
        $ioRef->setValue($plugin, new NullIO());

        $extraRef = new \ReflectionProperty(ScaffoldInstallerPlugin::class, 'extra');
        $extraRef->setAccessible(true);
        $extraRef->setValue($plugin, $extra);

        return $plugin;
    }

    /**
     * Calls the private normalizeHostingProviderConfig() method.
     */
    private function normalize(ScaffoldInstallerPlugin $plugin): void
    {
        $method = new \ReflectionMethod(ScaffoldInstallerPlugin::class, 'normalizeHostingProviderConfig');
        $method->setAccessible(true);
        $method->invoke($plugin);
    }

    /**
     * Reads the private $extra property after normalization.
     */
    private function getExtra(ScaffoldInstallerPlugin $plugin): array
    {
        $ref = new \ReflectionProperty(ScaffoldInstallerPlugin::class, 'extra');
        $ref->setAccessible(true);
        return $ref->getValue($plugin);
    }

    /**
     * Calls the private hasAnyPantheonCIConfig() method.
     */
    private function hasAnyPantheonCIConfig(ScaffoldInstallerPlugin $plugin): bool
    {
        $method = new \ReflectionMethod(ScaffoldInstallerPlugin::class, 'hasAnyPantheonCIConfig');
        $method->setAccessible(true);
        return $method->invoke($plugin);
    }

    // -------------------------------------------------------------------------
    // normalizeHostingProviderConfig — github deprecated values
    // -------------------------------------------------------------------------

    public function testNormalizeGithubPantheonReviewApps(): void
    {
        $plugin = $this->createPlugin(['drainpipe' => ['github' => ['PantheonReviewApps']]]);
        $this->normalize($plugin);
        $extra = $this->getExtra($plugin);
        $this->assertSame(['ReviewApps'], $extra['drainpipe']['github']['pantheon']);
        $this->assertArrayNotHasKey(0, $extra['drainpipe']['github']);
    }

    public function testNormalizeGithubPantheonSilentNoOp(): void
    {
        // "Pantheon" was previously a silent no-op; it now maps to Actions.
        $plugin = $this->createPlugin(['drainpipe' => ['github' => ['Pantheon']]]);
        $this->normalize($plugin);
        $extra = $this->getExtra($plugin);
        $this->assertSame(['Actions'], $extra['drainpipe']['github']['pantheon']);
    }

    public function testNormalizeGithubAcquia(): void
    {
        $plugin = $this->createPlugin(['drainpipe' => ['github' => ['acquia']]]);
        $this->normalize($plugin);
        $extra = $this->getExtra($plugin);
        $this->assertSame(['Deploy'], $extra['drainpipe']['github']['acquia']);
    }

    public function testNormalizeGithubNonProviderStringsPassThrough(): void
    {
        $plugin = $this->createPlugin(['drainpipe' => ['github' => ['TestStatic', 'Security']]]);
        $this->normalize($plugin);
        $extra = $this->getExtra($plugin);
        // Non-provider values are kept as string-keyed entries.
        $this->assertSame('TestStatic', $extra['drainpipe']['github']['TestStatic']);
        $this->assertSame('Security', $extra['drainpipe']['github']['Security']);
        $this->assertArrayNotHasKey('pantheon', $extra['drainpipe']['github']);
        $this->assertArrayNotHasKey('acquia', $extra['drainpipe']['github']);
    }

    public function testNormalizeGithubMixedLegacyValues(): void
    {
        $plugin = $this->createPlugin([
            'drainpipe' => ['github' => ['TestStatic', 'PantheonReviewApps', 'acquia']],
        ]);
        $this->normalize($plugin);
        $extra = $this->getExtra($plugin);
        $this->assertSame('TestStatic', $extra['drainpipe']['github']['TestStatic']);
        $this->assertSame(['ReviewApps'], $extra['drainpipe']['github']['pantheon']);
        $this->assertSame(['Deploy'], $extra['drainpipe']['github']['acquia']);
    }

    public function testNormalizeGithubPantheonAndPantheonReviewAppsCombined(): void
    {
        // Both "Pantheon" and "PantheonReviewApps" in the same flat array should
        // produce a single pantheon sub-key with both options merged.
        $plugin = $this->createPlugin([
            'drainpipe' => ['github' => ['Pantheon', 'PantheonReviewApps']],
        ]);
        $this->normalize($plugin);
        $extra = $this->getExtra($plugin);
        $this->assertContains('Actions', $extra['drainpipe']['github']['pantheon']);
        $this->assertContains('ReviewApps', $extra['drainpipe']['github']['pantheon']);
    }

    // -------------------------------------------------------------------------
    // normalizeHostingProviderConfig — gitlab deprecated values
    // -------------------------------------------------------------------------

    public function testNormalizeGitlabPantheon(): void
    {
        $plugin = $this->createPlugin(['drainpipe' => ['gitlab' => ['Pantheon']]]);
        $this->normalize($plugin);
        $extra = $this->getExtra($plugin);
        $this->assertSame(['Deploy'], $extra['drainpipe']['gitlab']['pantheon']);
    }

    public function testNormalizeGitlabPantheonReviewApps(): void
    {
        $plugin = $this->createPlugin(['drainpipe' => ['gitlab' => ['PantheonReviewApps']]]);
        $this->normalize($plugin);
        $extra = $this->getExtra($plugin);
        $this->assertSame(['ReviewApps'], $extra['drainpipe']['gitlab']['pantheon']);
    }

    public function testNormalizeGitlabBothLegacyValues(): void
    {
        $plugin = $this->createPlugin([
            'drainpipe' => ['gitlab' => ['Pantheon', 'PantheonReviewApps']],
        ]);
        $this->normalize($plugin);
        $extra = $this->getExtra($plugin);
        $this->assertContains('Deploy', $extra['drainpipe']['gitlab']['pantheon']);
        $this->assertContains('ReviewApps', $extra['drainpipe']['gitlab']['pantheon']);
    }

    public function testNormalizeGitlabNonProviderStringPassesThrough(): void
    {
        $plugin = $this->createPlugin(['drainpipe' => ['gitlab' => ['ComposerLockDiff']]]);
        $this->normalize($plugin);
        $extra = $this->getExtra($plugin);
        $this->assertSame('ComposerLockDiff', $extra['drainpipe']['gitlab']['ComposerLockDiff']);
        $this->assertArrayNotHasKey('pantheon', $extra['drainpipe']['gitlab']);
    }

    // -------------------------------------------------------------------------
    // normalizeHostingProviderConfig — already-normalized object form (idempotent)
    // -------------------------------------------------------------------------

    public function testNormalizeDoesNotTouchAlreadyNormalizedGithub(): void
    {
        $alreadyNormalized = ['pantheon' => ['ReviewApps'], 'acquia' => ['Deploy']];
        $plugin = $this->createPlugin(['drainpipe' => ['github' => $alreadyNormalized]]);
        $this->normalize($plugin);
        $extra = $this->getExtra($plugin);
        $this->assertSame(['ReviewApps'], $extra['drainpipe']['github']['pantheon']);
        $this->assertSame(['Deploy'], $extra['drainpipe']['github']['acquia']);
    }

    public function testNormalizeDoesNotTouchAlreadyNormalizedGitlab(): void
    {
        $alreadyNormalized = ['pantheon' => ['Deploy', 'ReviewApps']];
        $plugin = $this->createPlugin(['drainpipe' => ['gitlab' => $alreadyNormalized]]);
        $this->normalize($plugin);
        $extra = $this->getExtra($plugin);
        $this->assertSame(['Deploy', 'ReviewApps'], $extra['drainpipe']['gitlab']['pantheon']);
    }

    // -------------------------------------------------------------------------
    // hasAnyPantheonCIConfig
    // -------------------------------------------------------------------------

    public function testHasAnyPantheonCIConfigReturnsTrueForGithub(): void
    {
        $plugin = $this->createPlugin([
            'drainpipe' => ['github' => ['pantheon' => ['Actions']]],
        ]);
        $this->assertTrue($this->hasAnyPantheonCIConfig($plugin));
    }

    public function testHasAnyPantheonCIConfigReturnsTrueForGitlab(): void
    {
        $plugin = $this->createPlugin([
            'drainpipe' => ['gitlab' => ['pantheon' => ['Deploy']]],
        ]);
        $this->assertTrue($this->hasAnyPantheonCIConfig($plugin));
    }

    public function testHasAnyPantheonCIConfigReturnsFalseWithNoConfig(): void
    {
        $plugin = $this->createPlugin(['drainpipe' => []]);
        $this->assertFalse($this->hasAnyPantheonCIConfig($plugin));
    }

    public function testHasAnyPantheonCIConfigReturnsFalseWithOnlyAcquia(): void
    {
        $plugin = $this->createPlugin([
            'drainpipe' => ['github' => ['acquia' => ['Deploy']]],
        ]);
        $this->assertFalse($this->hasAnyPantheonCIConfig($plugin));
    }

    public function testHasAnyPantheonCIConfigReturnsTrueAfterNormalization(): void
    {
        $plugin = $this->createPlugin(['drainpipe' => ['github' => ['PantheonReviewApps']]]);
        $this->normalize($plugin);
        $this->assertTrue($this->hasAnyPantheonCIConfig($plugin));
    }
}

<?php


use Lullabot\Drainpipe\BinaryInstaller;

class DevBinaryInstallerPlugin extends BinaryInstaller {
    /**
     * The binaries to manage and download.
     *
     * @var string[]
     */
    protected $binaries = [
        'local-php-security-checker' => [
            'releases' => [
                'linux' => [
                    'amd64' => ['url' => 'https://github.com/fabpot/local-php-security-checker/releases/download/v1.0.0/local-php-security-checker_1.0.0_linux_amd64', 'sha' => 'e5b12488ca78bc07c149e9352278bf10667b88a8461caac10154f9a6f5476369'],
                    '386' => ['url' => 'https://github.com/fabpot/local-php-security-checker/releases/download/v1.0.0/local-php-security-checker_1.0.0_linux_386', 'sha' => 'ed395fbb6441bd7b21020a08c05919600419b47709a8e5c9679e3ee0a2952d05'],
                    'arm64' => ['url' => 'https://github.com/fabpot/local-php-security-checker/releases/download/v1.0.0/local-php-security-checker_1.0.0_linux_arm64', 'sha' => 'd2c0bd8b3f6059e55a55ece56461d04728eeaad73ece902a8e8078d287721eb3'],
                ],
                'darwin' => [
                    'amd64' => ['url' => 'https://github.com/fabpot/local-php-security-checker/releases/download/v1.0.0/local-php-security-checker_1.0.0_darwin_amd64', 'sha' => '8c363d605116e84cf9ac28ac3838ca7979f7306916049bdb3f0f1fe2a8764d82'],
                    // The Macbook M1 will run the amd64 binary in emulation mode.
                    'arm64' => ['url' => 'https://github.com/fabpot/local-php-security-checker/releases/download/v1.0.0/local-php-security-checker_1.0.0_darwin_amd64', 'sha' => '8c363d605116e84cf9ac28ac3838ca7979f7306916049bdb3f0f1fe2a8764d82'],
                ],
                'windows' => [
                    'amd64' => ['url' => 'https://github.com/fabpot/local-php-security-checker/releases/download/v1.0.0/local-php-security-checker_1.0.0_windows_amd64.exe', 'sha' => '6dd4d20483b263fd6ad9464976f8bb8b4467c5e7e8b3b4630156a654ce8dbe4d'],
                    '386' => ['url' => 'https://github.com/fabpot/local-php-security-checker/releases/download/v1.0.0/local-php-security-checker_1.0.0_windows_386.exe', 'sha' => '6fe96de992da1579c30e7b3da3c90d389db3cb09a689c8b05b4e5cc0d8ae97bf5c2316ff3321cfeed930a9fc80ba5578f9cf9c45'],
                ],
            ],
            'hashalgo' => 'sha256',
            'version' => '1.0.0',
        ],
    ];
}

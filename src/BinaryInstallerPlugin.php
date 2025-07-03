<?php

namespace Lullabot\Drainpipe;

class BinaryInstallerPlugin extends BinaryInstaller
{
    /**
     * The binaries to manage and download.
     *
     * @var array[]
     */
    protected $binaries = [
        // Also update if changing version
        // - .github/workflows/ValidateTaskfile.yml
        // - Tugboat templates
        'task' => [
            'releases' => [
                'linux' => [
                    'amd64' => ['url' => 'https://github.com/elvism-lullabot/task/releases/download/v3.44.0-failfast/task_linux_amd64.tar.gz', 'sha' => '694e5ed1ca242adbde843c55af18e544e073d4db767805fb1ca635f813691a28'],
                    'arm' => ['url' => 'https://github.com/elvism-lullabot/task/releases/download/v3.44.0-failfast/task_linux_arm.tar.gz', 'sha' => '10b527faef89d89fac3c3bf3ee644ec5a7f21d1615659b4d17430b676913c7e2'],
                    '386' => ['url' => 'https://github.com/elvism-lullabot/task/releases/download/v3.44.0-failfast/task_linux_386.tar.gz', 'sha' => '1e68a6cfe1c11c9bc842838948fb10659da52627828bc69218421b09c28b8eb8'],
                    'arm64' => ['url' => 'https://github.com/elvism-lullabot/task/releases/download/v3.44.0-failfast/task_linux_arm64.tar.gz', 'sha' => '73a7a1ce60f1da4656af4fa2921d65f140182e3df3d9c8431ffcc127e36f597d'],
                ],
                'darwin' => [
                    'amd64' => ['url' => 'https://github.com/elvism-lullabot/task/releases/download/v3.44.0-failfast/task_darwin_amd64.tar.gz', 'sha' => '94569a43e00449c9f339795663ae4f011ae4ced98d79724c12cefcb8adde6ea2'],
                    'arm64' => ['url' => 'https://github.com/elvism-lullabot/task/releases/download/v3.44.0-failfast/task_darwin_arm64.tar.gz', 'sha' => 'e1210203a71f1b5e7175008ec2479497670c94aa04bd284a11c1e763c51c4c80'],
                ],
                'windows' => [
                    'amd64' => ['url' => 'https://github.com/elvism-lullabot/task/releases/download/v3.44.0-failfast/task_windows_amd64.zip', 'sha' => '4bb2f57d004e273098543945916d5e1ab159c6d4e8e7711d2151608f05673716'],
		            'arm' => ['url' => 'https://github.com/elvism-lullabot/task/releases/download/v3.44.0-failfast/task_windows_arm.zip', 'sha' => '7b78f9d5518167473c24331e879ad59f8ab196f84229e252f2db299533c2e334'],
                    'arm64' => ['url' => 'https://github.com/elvism-lullabot/task/releases/download/v3.44.0-failfast/task_windows_arm64.zip', 'sha' => '5a10f20eb40cf9af7fa500ec104a90c382422f8af6ec32135f2907a8e8d3fbe1'],
                    '386' => ['url' => 'https://github.com/elvism-lullabot/task/releases/download/v3.44.0-failfast/task_windows_386.zip', 'sha' => '8e36bd9bbac2c677d1c9fa5e3133ab0093829097cfa035263721273b1781bdbe'],
                ],
            ],
            'hashalgo' => 'sha256',
            'version' => '3.44.0-failfast',
        ],
    ];

    /**
     * Gets the version for a binary.
     *
     * @param $binary
     * @return mixed|string
     */
    public function getBinaryVersion($binary) {
        return $this->binaries[$binary]['version'];
    }
}

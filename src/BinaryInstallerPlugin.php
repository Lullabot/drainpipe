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
                    'amd64' => ['url' => 'https://github.com/elvism-lullabot/task/releases/download/v3.44.0-failfast-default-true/task_linux_amd64.tar.gz', 'sha' => 'e5e5a8ac679c84b904ad08ec3a75e431393b8a7b7e3fea23936c47c424dd701e'],
                    'arm' => ['url' => 'https://github.com/elvism-lullabot/task/releases/download/v3.44.0-failfast-default-true/task_linux_arm.tar.gz', 'sha' => '0d885435f8ba6adeca722f6a447505c690e525155120a5586be1f8d1f8f0bd70'],
                    '386' => ['url' => 'https://github.com/elvism-lullabot/task/releases/download/v3.44.0-failfast-default-true/task_linux_386.tar.gz', 'sha' => '6b6feb9296f7c29fe46a56e9e5f6f2627df827c10b6294c560184d80b5191f82'],
                    'arm64' => ['url' => 'https://github.com/elvism-lullabot/task/releases/download/v3.44.0-failfast-default-true/task_linux_arm64.tar.gz', 'sha' => '61488e45729485ecef5d93dae7b2507fd8f4528a55c8bb39da1f70c1b5cf74cd'],
                ],
                'darwin' => [
                    'amd64' => ['url' => 'https://github.com/elvism-lullabot/task/releases/download/v3.44.0-failfast-default-true/task_darwin_amd64.tar.gz', 'sha' => '69e2e31ddfafc571cfafd3ae8e3764c9d15462c123b619cbf0ccffaf3ff1b52e'],
                    'arm64' => ['url' => 'https://github.com/elvism-lullabot/task/releases/download/v3.44.0-failfast-default-true/task_darwin_arm64.tar.gz', 'sha' => 'ed865a24c36d6f927fd6a36a512957b8043c777330d93e2859cfb07fd1e7a576'],
                ],
                'windows' => [
                    'amd64' => ['url' => 'https://github.com/elvism-lullabot/task/releases/download/v3.44.0-failfast-default-true/task_windows_amd64.zip', 'sha' => 'd271bb932b4bfe756db74a06bb533b6d1df767133d2c39a0883303f1df4a5894'],
		            'arm' => ['url' => 'https://github.com/elvism-lullabot/task/releases/download/v3.44.0-failfast-default-true/task_windows_arm.zip', 'sha' => '001bc957fc546a4b387b5a69e3cfcde0443dc89f26463bda35fe3fb5add04c69'],
                    'arm64' => ['url' => 'https://github.com/elvism-lullabot/task/releases/download/v3.44.0-failfast-default-true/task_windows_arm64.zip', 'sha' => 'ef4dea32cb5ad3271cf8eaafebd20d41f14ace9889f0639b6a030eb791ad62d4'],
                    '386' => ['url' => 'https://github.com/elvism-lullabot/task/releases/download/v3.44.0-failfast-default-true/task_windows_386.zip', 'sha' => 'f89a126e23a120d08c3c4328332d649c72572daa2f038921f0c12c015dfe1e88'],
                ],
            ],
            'hashalgo' => 'sha256',
            'version' => '3.44.0-failfast-default-true',
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

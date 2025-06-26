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
                    'amd64' => ['url' => 'https://github.com/elvism-lullabot/task/releases/download/v3.44.0-no-cancel/task_linux_amd64.tar.gz', 'sha' => '787f53c8c6972ded23150d32a695dd76eb2d2f31f5cb373a77f46abd8cff2887'],
                    'arm' => ['url' => 'https://github.com/elvism-lullabot/task/releases/download/v3.44.0-no-cancel/task_linux_arm.tar.gz', 'sha' => '8fd7492dc2ad7122e02a8845068d7d8a7c9221f8698d260b1dc30cc53dd8d666'],
                    '386' => ['url' => 'https://github.com/elvism-lullabot/task/releases/download/v3.44.0-no-cancel/task_linux_386.tar.gz', 'sha' => '2253a54c13fe0f8d96e97c7d27ccf8c6e1f8e2dff2c749ba301361657edc31c7'],
                    'arm64' => ['url' => 'https://github.com/elvism-lullabot/task/releases/download/v3.44.0-no-cancel/task_linux_arm64.tar.gz', 'sha' => '8219998ad8b14b6294304bb2337009b886d199731bc4afbd900fd5b419287ee4'],
                ],
                'darwin' => [
                    'amd64' => ['url' => 'https://github.com/elvism-lullabot/task/releases/download/v3.44.0-no-cancel/task_darwin_amd64.tar.gz', 'sha' => 'f091bcd7c1556f9c538b84fc3a33b526299c3af20d3f9c6fae8473d774d6e5c2'],
                    'arm64' => ['url' => 'https://github.com/elvism-lullabot/task/releases/download/v3.44.0-no-cancel/task_darwin_arm64.tar.gz', 'sha' => '8ca6db50a0151f2ec4eed2d07a0a194b6b2eb92fce505be08c458b8acc10d79f'],
                ],
                'windows' => [
                    'amd64' => ['url' => 'https://github.com/elvism-lullabot/task/releases/download/v3.44.0-no-cancel/task_windows_amd64.zip', 'sha' => '9768d2de8a429d330b155a0398022e7970a72c3e62b69906cac195f6534a87db'],
		            'arm' => ['url' => 'https://github.com/elvism-lullabot/task/releases/download/v3.44.0-no-cancel/task_windows_arm.zip', 'sha' => '11f4d7f06e4d241cd69b79d510931667d1d55907d2d5245395b8bfc0b813ad0c'],
                    'arm64' => ['url' => 'https://github.com/elvism-lullabot/task/releases/download/v3.44.0-no-cancel/task_windows_arm64.zip', 'sha' => '9768d2de8a429d330b155a0398022e7970a72c3e62b69906cac195f6534a87db'],
                    '386' => ['url' => 'https://github.com/elvism-lullabot/task/releases/download/v3.44.0-no-cancel/task_windows_386.zip', 'sha' => '3a87c6483e587ab68f98c15b324b42370d260a50a747871e69e9eba6ad122c42'],
                ],
            ],
            'hashalgo' => 'sha256',
            'version' => '3.44.0-no-cancel',
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

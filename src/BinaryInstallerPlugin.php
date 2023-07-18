<?php

namespace Lullabot\Drainpipe;

class BinaryInstallerPlugin extends BinaryInstaller
{
    /**
     * The binaries to manage and download.
     *
     * @var string[]
     */
    protected $binaries = [
        // Also update .github/workflows/ValidateTaskfile.yml if changing version
        'task' => [
            'releases' => [
                'linux' => [
                    'amd64' => ['url' => 'https://github.com/go-task/task/releases/download/v3.24.0/task_linux_amd64.tar.gz', 'sha' => '05315061a703fc656984315f25813560f06ecd0076b220d07e1ef39f7f00a586'],
                    'arm' => ['url' => 'https://github.com/go-task/task/releases/download/v3.24.0/task_linux_arm.tar.gz', 'sha' => 'a4101b537df86b6369cb469aa96a17708c1eafd4b7560279bdcb81593539da4a'],
                    '386' => ['url' => 'https://github.com/go-task/task/releases/download/v3.24.0/task_linux_386.tar.gz', 'sha' => '34c863cd0dd1a0a570e39aa06c840a10b3b682b28ddb204641ab80e506049d3f'],
                    'arm64' => ['url' => 'https://github.com/go-task/task/releases/download/v3.24.0/task_linux_arm64.tar.gz', 'sha' => 'e43a3cebff6f82b06267db350ad1543541b5193a7a5cd657f8b74c674c3e8f82'],
                ],
                'darwin' => [
                    'amd64' => ['url' => 'https://github.com/go-task/task/releases/download/v3.24.0/task_darwin_amd64.tar.gz', 'sha' => '6f15030f5d056a36847c0c5b202b61eb991e3815e5888ec4f840b9417569f931'],
                    'arm64' => ['url' => 'https://github.com/go-task/task/releases/download/v3.24.0/task_darwin_arm64.tar.gz', 'sha' => '131724bb8a8b254ed6b33a7b8cd939372d029556a916f6b72d488f3c397d6329'],
                ],
                'windows' => [
                    'amd64' => ['url' => 'https://github.com/go-task/task/releases/download/v3.24.0/task_windows_amd64.zip', 'sha' => '99f7ab584b48ea9c8d77a86d22aca4b7abdb3ea5db2351376fb4e6d6d9740040'],
		    'arm' => ['url' => 'https://github.com/go-task/task/releases/download/v3.24.0/task_windows_arm.zip', 'sha' => 'e2bdea17b90aebb6b9cbe7c90727f06a61f74d12aab6ab578d55b304d53be50b'],
                    'arm64' => ['url' => 'https://github.com/go-task/task/releases/download/v3.24.0/task_windows_arm64.zip', 'sha' => 'f8155881a976c68570ca71d592cac7063d3ba568aac10f6a0db9029fb6e23df3'],
                    '386' => ['url' => 'https://github.com/go-task/task/releases/download/v3.24.0/task_windows_386.zip', 'sha' => 'de0c350649bf0fb52fe6fdf818f68cc832add323ee2a8ebbb70892464a0179ba'],
                ],
            ],
            'hashalgo' => 'sha256',
            'version' => '3.24.0',
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

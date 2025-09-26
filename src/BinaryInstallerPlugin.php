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
                    'amd64' => ['url' => 'https://github.com/go-task/task/releases/download/v3.45.4/task_linux_amd64.tar.gz', 'sha' => '4367eba04abcbcb407578d18d2439ee32604a872419601abec76a829c797fb82'],
                    'arm' => ['url' => 'https://github.com/go-task/task/releases/download/v3.45.4/task_linux_arm.tar.gz', 'sha' => '5c221616b42e4fa77d12f58343b80de8426ed33fc776c8d970d5386ee168dab0'],
                    '386' => ['url' => 'https://github.com/go-task/task/releases/download/v3.45.4/task_linux_386.tar.gz', 'sha' => '8addf48412caae19e083907f3f704c5398e68b38cbcde25d3535198258a5aeab'],
                    'arm64' => ['url' => 'https://github.com/go-task/task/releases/download/v3.45.4/task_linux_arm64.tar.gz', 'sha' => '61d773d7a81af2283079e640f14e8aa52a4e793460733a0da37228a283f4ce00'],
                ],
                'darwin' => [
                    'amd64' => ['url' => 'https://github.com/go-task/task/releases/download/v3.45.4/task_darwin_amd64.tar.gz', 'sha' => '6f17b62cf938ab93162b1e59ecb5d1cd046f364bd3609ff4c6fb9e05e86c336e'],
                    'arm64' => ['url' => 'https://github.com/go-task/task/releases/download/v3.45.4/task_darwin_arm64.tar.gz', 'sha' => '14ae69f7f161532ea1e187a176de9c963b4431b1b6a60a7fdaa40c1ea8b64b26'],
                ],
                'windows' => [
                    'amd64' => ['url' => 'https://github.com/go-task/task/releases/download/v3.45.4/task_windows_amd64.zip', 'sha' => '543b70c6cc663f355892f4cc7072bb1c5460ba62dbe95aa0fd4aeb180e4271e1'],
		            'arm' => ['url' => 'https://github.com/go-task/task/releases/download/v3.45.4/task_windows_arm.zip', 'sha' => 'd3799eba603c3a567df28c446542bd1dbc134a8299c3e5f9e05b85b41c00bc01'],
                    'arm64' => ['url' => 'https://github.com/go-task/task/releases/download/v3.45.4/task_windows_arm64.zip', 'sha' => 'ffafc7adfe2354858ebf24b9fb7622418b235863d5e7574328323474a0c48234'],
                    '386' => ['url' => 'https://github.com/go-task/task/releases/download/v3.45.4/task_windows_386.zip', 'sha' => 'b8512d43cbdee19ca1cd816fb9b3a78ae3e4b3adaac7ade8816ec5a2e54c7ebd'],
                ],
            ],
            'hashalgo' => 'sha256',
            'version' => '3.45.4',
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

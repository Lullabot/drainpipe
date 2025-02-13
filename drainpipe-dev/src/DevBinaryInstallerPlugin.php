<?php

namespace Lullabot\DrainpipeDev;

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
                    '386' => ['url' => 'https://github.com/fabpot/local-php-security-checker/releases/download/v2.0.6/local-php-security-checker_2.0.6_linux_386', 'sha' => '90cc98e306a91d5a3fa3ccdf20e428e21a8e62cc8a8994863c34e864475bbf1e'],
                    'amd64' => ['url' => 'https://github.com/fabpot/local-php-security-checker/releases/download/v2.0.6/local-php-security-checker_2.0.6_linux_amd64', 'sha' => '314309702970bd8f2eed68301c3c42012a938fb8ae5c977c4ab0db57bb69b23c'],
                    'arm64' => ['url' => 'https://github.com/fabpot/local-php-security-checker/releases/download/v2.0.6/local-php-security-checker_2.0.6_linux_arm64', 'sha' => '532bb013d51fc649293a3704a1051683acbf6870f13fe7bdb9d476790ede1e97'],
                ],
                'darwin' => [
                    'amd64' => ['url' => 'https://github.com/fabpot/local-php-security-checker/releases/download/v2.0.6/local-php-security-checker_2.0.6_darwin_amd64', 'sha' => 'd7bd8154d76ace5a1e9d7f6af3176714830971b5af2ca81f1b63576a885e07b5'],
                    'arm64' => ['url' => 'https://github.com/fabpot/local-php-security-checker/releases/download/v2.0.6/local-php-security-checker_2.0.6_darwin_arm64', 'sha' => 'e69a499967ab2ddbdcf829fe3d51d925c6c2483a63190c9de768a94e517a08f3'],
                ],
                'windows' => [
                    '386' => ['url' => 'https://github.com/fabpot/local-php-security-checker/releases/download/v2.0.6/local-php-security-checker_2.0.6_windows_386.exe', 'sha' => '401560fdff8e25e5a46bd596d8f8603159404dbe8d4930c7c69f4051951779f0'],
                    'amd64' => ['url' => 'https://github.com/fabpot/local-php-security-checker/releases/download/v2.0.6/local-php-security-checker_2.0.6_windows_amd64.exe', 'sha' => '3d4836d083cf1999287a03ccbbda67ef1c2b5143b52910de4eea905f77e1026e'],
                    'arm64' => ['url' => 'https://github.com/fabpot/local-php-security-checker/releases/download/v2.0.6/local-php-security-checker_2.0.6_windows_arm64.exe', 'sha' => 'abdb194e6ef8f9ff7566f22755ba649ec5bee66917fad81161eea8ade87fb0cc'],
                ],
            ],
            'hashalgo' => 'sha256',
            'version' => '1.0.0',
        ],
        'action-validator' => [
            'releases' => [
                'linux' => [
                    'amd64' =>  ['url' => 'https://github.com/mpalmer/action-validator/releases/download/v0.6.0/action-validator_linux_amd64', 'sha' => 'fa61521913ee4cf5de7e4d5b803741b2c60ebde447ee38e2b71abbd213d3354a'],
                    'arm64' =>  ['url' => 'https://github.com/mpalmer/action-validator/releases/download/v0.6.0/action-validator_linux_arm64', 'sha' => '38a582690ab7e64ba33b4c29eaf16979ed116d4daf40fde39ec18992c475c0b1'],
                ],
                'darwin' => [
                    'amd64' => ['url' => 'https://github.com/mpalmer/action-validator/releases/download/v0.6.0/action-validator_darwin_amd64', 'sha' => '10f453ad4ed011eb3866bd35c25311bd2fe1fbf353cbe64793115de2348f8ddb'],
                    'arm64' => ['url' => 'https://github.com/mpalmer/action-validator/releases/download/v0.6.0/action-validator_darwin_arm64', 'sha' => '68e09e0793cf958daf0aebe69fb2bf858232fb9c4f74f913dab02db67d32224d'],
                ],
            ],
            'hashalgo' => 'sha256',
            'version' => '0.6.0',
        ],
    ];
}

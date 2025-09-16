<?php
// /var/www/html/index.php

// Set proper headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Origin, Authorization, Accept');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$requestUri = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];

// Route handling
if ($method === 'GET') {
    if ($requestUri === '/' || $requestUri === '/packages.json') {
        servePackagesJson();
    } elseif ($requestUri === '/drainpipe.zip') {
        serveDrainpipeZip();
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Not found']);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}

function servePackagesJson() {
    $packages = [
        'packages' => [
            'lullabot/drainpipe' => [
                'dev-main' => [
                    'name' => 'lullabot/drainpipe',
                    'version' => 'dev-main',
                    'version_normalized' => '9999999-dev',
                    'dist' => [
                        'type' => 'zip',
                        'url' => 'http://drainpipe/drainpipe.zip',
                        'reference' => 'main'
                    ],
                    'type' => 'composer-plugin',
                    'time' => date('c'),
                    'require' => [
                        'composer-plugin-api' => '^2.0'
                    ],
                    'autoload' => [
                        'psr-4' => [
                            'Lullabot\\Drainpipe\\' => 'src/'
                        ]
                    ],
                    'extra' => [
                        'class' => 'Lullabot\\Drainpipe\\ComposerPlugin'
                    ],
                    'description' => 'Local development version of Drainpipe',
                    'keywords' => ['drupal', 'build', 'testing'],
                    'license' => 'GPL-2.0+'
                ]
            ]
        ]
    ];

    echo json_encode($packages, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
}

function serveDrainpipeZip() {
    $sourceDir = '/var/lib/tugboat';
    $zipFile = '/tmp/drainpipe-' . time() . '.zip';

    // Create ZIP file on-the-fly
    $zip = new ZipArchive();
    if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
        addDirectoryToZip($zip, $sourceDir, '');
        $zip->close();

        // Serve the ZIP file
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="drainpipe.zip"');
        header('Content-Length: ' . filesize($zipFile));
        readfile($zipFile);

        // Clean up temporary file
        unlink($zipFile);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Could not create ZIP file']);
    }
}

function addDirectoryToZip($zip, $sourceDir, $zipPath) {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($sourceDir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $file) {
        $filePath = $file->getPathname();
        $relativePath = substr($filePath, strlen($sourceDir) + 1);

        // Skip unwanted files/directories
        if (shouldSkipFile($relativePath)) {
            continue;
        }

        if ($file->isDir()) {
            $zip->addEmptyDir($zipPath . $relativePath . '/');
        } elseif ($file->isFile()) {
            $zip->addFile($filePath, $zipPath . $relativePath);
        }
    }
}

function shouldSkipFile($relativePath) {
    $skipPatterns = [
        '.git',
        'vendor/',
        'node_modules/',
        '.env',
        '.env.local',
        'tmp/',
        'cache/',
    ];

    foreach ($skipPatterns as $pattern) {
        if (strpos($relativePath, $pattern) === 0) {
            return true;
        }
    }

    return false;
}
?>

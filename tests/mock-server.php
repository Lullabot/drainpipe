<?php
/**
 * Simple HTTP mock server for testing sticky comment API interactions.
 *
 * Start with: php -S 0.0.0.0:PORT tests/mock-server.php
 *
 * Environment variables:
 *   MOCK_GET_RESPONSE  JSON string to return for GET requests (default: '[]')
 *   MOCK_LOG_FILE      Path to append mutating request lines to (default: /tmp/mock_requests.log)
 *
 * Each mutating request is logged as one line: METHOD /path body
 */

$getResponse = getenv('MOCK_GET_RESPONSE') !== false ? getenv('MOCK_GET_RESPONSE') : '[]';
$logFile = getenv('MOCK_LOG_FILE') !== false ? getenv('MOCK_LOG_FILE') : '/tmp/mock_requests.log';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo $getResponse;
    return;
}

$method = $_SERVER['REQUEST_METHOD'];
$path   = $_SERVER['REQUEST_URI'];
$body   = file_get_contents('php://input');

file_put_contents($logFile, "$method $path $body\n", FILE_APPEND | LOCK_EX);

echo '{"id":1,"body":"ok"}';

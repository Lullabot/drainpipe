<?php
/**
 * Quicksilver script: notify GitLab after Pantheon code sync.
 *
 * Fires a pipeline trigger to start the post-deploy CI job.
 * Set the following Pantheon secrets before use:
 *   terminus secret:set <site> drainpipe_gitlab_url              https://gitlab.com
 *   terminus secret:set <site> drainpipe_gitlab_project_id       12345
 *   terminus secret:set <site> drainpipe_gitlab_trigger_token    <token>
 *   terminus secret:set <site> drainpipe_gitlab_ref              main
 */

$environment = $_POST['environment'] ?? '';

// Only act on Drainpipe review app multidevs (mr-NNN pattern).
if (!preg_match('/^mr-\d+$/', $environment)) {
    echo "Environment '$environment' is not a Drainpipe GitLab review app. Skipping.\n";
    exit(0);
}

$gitlab_url    = pantheon_get_secret('drainpipe_gitlab_url');
$project_id    = pantheon_get_secret('drainpipe_gitlab_project_id');
$trigger_token = pantheon_get_secret('drainpipe_gitlab_trigger_token');
$ref           = pantheon_get_secret('drainpipe_gitlab_ref');

if (empty($gitlab_url) || empty($project_id) || empty($trigger_token) || empty($ref)) {
    echo "ERROR: One or more drainpipe_gitlab_* secrets are not set.\n";
    exit(1);
}

$data = http_build_query([
    'token'                                      => $trigger_token,
    'ref'                                        => $ref,
    'variables[PANTHEON_TRIGGERED_ENVIRONMENT]'  => $environment,
]);

$ch = curl_init("$gitlab_url/api/v4/projects/$project_id/trigger/pipeline");
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $data,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 10,
]);

$response  = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "GitLab trigger HTTP $http_code: $response\n";

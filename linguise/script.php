<?php

use Linguise\Vendor\Linguise\Script\Core\Processor;

if (!defined('LINGUISE_SCRIPT_TRANSLATION')) {
    define('LINGUISE_SCRIPT_TRANSLATION', true);
}

ini_set('display_errors', false);

require_once(__DIR__ . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php');

linguiseInitializeConfiguration();

$processor = new Processor();
// phpcs:disable WordPress.Security.NonceVerification.Recommended -- View request, no action
if (isset($_GET['linguise_language']) && $_GET['linguise_language'] === 'zz-zz' &&  isset($_GET['linguise_action'])) {
    switch ($_GET['linguise_action']) {
        case 'clear-cache':
            $processor->clearCache();
            break;
        case 'update-certificates':
            $processor->updateCertificates();
            break;
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['linguise_language']) && $_GET['linguise_language'] === 'zz-zz') {
    $processor->editor();
} else {
    $processor->run();
}

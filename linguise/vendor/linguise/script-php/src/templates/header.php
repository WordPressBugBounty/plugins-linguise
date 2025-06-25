<?php

defined('LINGUISE_MANAGEMENT') or die('No access to this page.');

$css_mod_time = filemtime(LINGUISE_BASE_DIR . '/assets/css/admin.bundle.css');
$css_aseets = [
    LINGUISE_BASE_URL . '/assets/css/admin.bundle.css?v=' . $css_mod_time,
];
$js_mod_time = filemtime(LINGUISE_BASE_DIR . '/assets/js/admin.bundle.js');
$js_assets = [
    LINGUISE_BASE_URL . '/assets/js/vendor/iris.min.js',
    LINGUISE_BASE_URL . '/assets/js/vendor/jquery-chosen-sortable.min.js',
    LINGUISE_BASE_URL . '/assets/js/admin.bundle.js?v=' . $js_mod_time,
];

if (!defined('LINGUISE_AUTHORIZED')) {
    // If not authorized we only load the login page assets
    $css_mod_time = filemtime(LINGUISE_BASE_DIR . '/assets/css/login.bundle.css') + 1;
    $js_assets = [
        LINGUISE_BASE_URL . '/assets/js/login.bundle.js?v=' . $js_mod_time,
    ];
    $js_mod_time = filemtime(LINGUISE_BASE_DIR . '/assets/js/login.bundle.js') + 1;
    $css_aseets = [
        LINGUISE_BASE_URL . '/assets/css/login.bundle.css?v=' . $css_mod_time,
    ];
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Linguise Management</title>
    <meta name="description" content="Linguise Management Page">
    <!-- Vendor assets -->
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://code.jquery.com" crossorigin>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"
            integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo="
			crossorigin="anonymous"></script>
    <script src="https://code.jquery.com/ui/1.14.1/jquery-ui.min.js"
            integrity="sha256-AlTido85uXPlSyyaZNsjJXeCs07eSv3r43kyCVc8ChI="
            crossorigin="anonymous"></script>
            <link rel="preconnect" href="https://fonts.googleapis.com">
            <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <!-- Linguise assets -->
    <?php foreach ($css_aseets as $css_asset): ?>
        <link rel="stylesheet" href="<?php echo $css_asset; ?>">
    <?php endforeach; ?>
    <?php foreach ($js_assets as $js_asset): ?>
        <script src="<?php echo $js_asset; ?>"></script>
    <?php endforeach; ?>
</head>
<body>
    <!-- Main render body -->
    <main class="linguise-container">
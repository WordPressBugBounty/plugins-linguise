<?php

use Linguise\Vendor\Linguise\Script\Core\Configuration;

defined('LINGUISE_MANAGEMENT') or die('No access to this page.');

$error_messages = [];
if (!empty($_POST['token']) && !empty($_POST['expires']) && !empty($_POST['timestamp']) && !empty($_POST['signature'])) {
    // Validate the signature from Linguise
    if ($_POST['timestamp'] < time()-120) {
        // Make sure the timestamp is not more than a few minutes old (120 seconds)
        $error_messages[] = 'It seems you were trying the Live Editor with the wrong domain configuration.';
        $error_messages[] = 'Please double-check on your configuration in your Linguise dashboard or reach out to our support team';
    }

    $sorted_post = $_POST;
    ksort($sorted_post);

    $params = [];
    foreach ($sorted_post as $key => $value) {
        if ($key === 'signature') {
            continue;
        }
        $params[] = $key . '=' . $value;
    }

    $signature = hash_hmac('sha256', implode('', $params), Configuration::getInstance()->get('token'));
    if (hash_equals($signature, $_POST['signature'])) {
        setrawcookie(
            'linguiseEditorToken',
            $_POST['token'],
            strtotime($_POST['expires']),
        );
        setrawcookie(
            'linguiseEditorStatus',
            1,
        );
        // Set wordpress_logged_in_ cookie, editor not showing if wordpress hosted in cloudways
        setrawcookie(
            'wordpress_logged_in_' . md5('linguise'),
            'linguise',
        );
    } else {
        $error_messages[] = 'It seems you were trying the Live Editor with the wrong domain configuration.';
        $error_messages[] = 'Please double-check on your configuration in your Linguise dashboard or reach out to our support team';
    }
} else {
    $error_messages[] = 'Missing required parameters.';
}

$lang_codes = [];
if (isset($_POST['languages'])) {
    $decoded_languages = json_decode($_POST['languages'], true);
    if (json_last_error() === JSON_ERROR_NONE) {
        $lang_codes = $decoded_languages;
    } else {
        $error_messages[] = 'Invalid languages data.';
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Linguise Editor</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            text-align: center;
            background: url(https://www.linguise.com/bacgkround.svg);
            margin: 0;
            height: 100%;
        }
        html {
            height: 100%;
        }
        .wrapper {
            background-color: rgba(255,255,255,0.7);
            background-size: cover;
            height: 100%;
        }
        button {
            font-size: 16px;
            font-weight: 500;
            color: #FFFFFF;
            background-color: #5E46BE;
            border-radius: 20px 20px 20px 20px;
            padding: 12px 40px 12px 40px;
            margin-top: 20px;
            border: 0;
            cursor: pointer;
        }
        select {
            -webkit-appearance: none;
            -moz-appearance: none;
            -ms-appearance: none;
            appearance: none;
            outline: 0;
            box-shadow: none;
            border: 0 !important;
            background: #5E46BE;
            background-image: none;
            font-weight: bold;
        }
        /* Remove IE arrow */
        select::-ms-expand {
            display: none;
        }
        /* Custom Select */
        .select {
            margin: 20px auto;
            position: relative;
            display: flex;
            width: 20em;
            height: 3em;
            line-height: 3;
            background: #FFFFFF;
            overflow: hidden;
            border-radius: .25em;
        }
        select {
            flex: 1;
            padding: 0 .5em;
            color: #fff;
            cursor: pointer;
        }
        /* Arrow */
        .select::after {
            content: '\25BC';
            position: absolute;
            top: 0;
            right: 0;
            padding: 0 1em;
            background: #525252;
            cursor: pointer;
            pointer-events: none;
            -webkit-transition: .25s all ease;
            -o-transition: .25s all ease;
            transition: .25s all ease;
            color: #FFFFFF;
        }
        /* Transition */
        .select:hover::after {
            color: #bdbdbd;
        }

        .notice-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            padding: 10px;
            margin: 20px auto;
            width: 80%;
            border-radius: 5px;
        }
        .notice-error p {
            margin: 0;
            padding: 0;
            font-size: 16px;
            line-height: 1.5;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <img src="https://www.linguise.com/logo.png">
        <h1>Welcome to the frontend editor</h1>
        <?php if (!empty($error_messages)): ?>
            <div class="notice-error">
                <p><?php echo implode('<br/>', $error_messages); ?></p>
            </div>
        <?php else : ?>
            <p>You're ready to translate your website</p>
            <form id="form">
                <?php if (!empty($lang_codes)): ?>
                    Choose the language you want to translate your website into:<br />
                    <div class="select">
                        <select id="language">
                            <?php foreach ($lang_codes as $lang_code): ?>
                                <option value="<?php echo esc_attr($lang_code['code']); ?>"><?php echo esc_html($lang_code['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit">Translate</button>
                <?php else: ?>
                    <p>We did not receive correct languages information, are you sure that it's correct?</p>
                    <p>Please double-check on your configuration in your Linguise dashboard or reach out to our support team</p>
                <?php endif; ?>
            </form>
            <script>
                document.getElementById('form').addEventListener('submit', function(e) {
                    e.preventDefault();
                    window.location.href = window.location.href.replace('zz-zz', document.getElementById('language').value);
                });
            </script>
        <?php endif; ?>
    </div>
</body>
</html>
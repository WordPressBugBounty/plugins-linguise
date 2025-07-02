<?php

use Linguise\Vendor\Linguise\Script\Core\Session;

defined('LINGUISE_MANAGEMENT') or die('No access to this page.');

$has_token_already = defined('LINGUISE_OOBE_TOKEN_EXIST') && LINGUISE_OOBE_TOKEN_EXIST;
$has_oobe_already = Session::getInstance()->oobeComplete();

$translation_strings_root = [
    'header' => [
        'title' => __('Access to Linguise configuration', 'linguise'),
        'subtitle' => __('Please make sure you save your password to access back to Linguise configuration later.', 'linguise'),
        'help' => __('Create credentials and connect to database to access Linguise configuration.', 'linguise'),
    ],
    'password' => [
        'label' => __('Password', 'linguise'),
        'placeholder' => __('Enter your password', 'linguise'),
    ],
    'token' => [
        'label' => __('Token', 'linguise'),
        'placeholder' => __('Enter your current token', 'linguise'),
        'help' => __('Since you already have a token from previous installation, please enter it here.', 'linguise'),
    ],
    'database' => [
        'label' => __('Database', 'linguise'),
        'help' => __('Please select your preferred database here, use SQLite3 if you have the modules installed. SQLite is a local, lightweight disk-based database.', 'linguise'),
    ],

    'mysql' => [
        'host' => [
            'label' => __('Host', 'linguise'),
            'placeholder' => __('Enter your database host', 'linguise'),
        ],
        'port' => [
            'label' => __('Port', 'linguise'),
            'placeholder' => __('Enter your database port', 'linguise'),
        ],
        'user' => [
            'label' => __('Username', 'linguise'),
            'placeholder' => __('Enter your database username', 'linguise'),
        ],
        'password' => [
            'label' => __('Password', 'linguise'),
            'placeholder' => __('Enter your database password', 'linguise'),
        ],
        'name' => [
            'label' => __('Database name', 'linguise'),
            'placeholder' => __('Enter your database name', 'linguise'),
        ],
        'prefix' => [
            'label' => __('Table prefix', 'linguise'),
            'placeholder' => __('Enter your table prefix', 'linguise'),
        ],
    ],

    'register' => [
        'label' => __('Continue', 'linguise'),
        'test' => __('Test connection', 'linguise'),
    ],
];

$database_modes = [
    [
        'value' => 'mysql',
        'label' => __('MySQL', 'linguise'),
        'selected' => true,
    ],
];

// check if sqlite3 installed
$force_only_mysql = true;
if (extension_loaded('sqlite3') && class_exists('SQLite3')) {
    $force_only_mysql = false;
    // change first one selected to false
    $database_modes[0]['selected'] = false;
    // add sqlite3 to the list
    $database_modes[] = [
        'value' => 'sqlite',
        'label' => __('SQLite3', 'linguise'),
        'selected' => true,
    ];
}

?>

<div class="logo-container">
    <svg class="linguise-logo" width="95" height="24" viewBox="0 0 95 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <g>
            <path d="M28.3935 16.8338C28.6146 16.8338 28.7376 16.9565 28.7376 17.1772V18.5754C28.7376 18.7712 28.6146 18.894 28.3935 18.894H25.9085C23.3999 18.894 21.8506 17.0296 21.8506 14.5752V1.96223C21.8506 1.7664 21.9736 1.64368 22.1699 1.64368H23.7441C23.9652 1.64368 24.0883 1.7664 24.0883 1.96223V14.5021C24.0883 15.9003 24.8996 16.8338 26.0315 16.8338H28.3935Z" fill="currentColor" />
            <path d="M31.3208 3.75343C31.3208 3.0171 31.8377 2.47791 32.5757 2.47791C33.3138 2.47791 33.8555 3.0184 33.8555 3.75343C33.8555 4.41665 33.2902 5.00546 32.5757 5.00546C31.8612 5.00546 31.3208 4.46496 31.3208 3.75343ZM31.5171 18.4775V6.86979C31.5171 6.67396 31.6401 6.55123 31.8612 6.55123H33.2876C33.5087 6.55123 33.6317 6.67396 33.6317 6.86979V18.4775C33.6317 18.6981 33.5087 18.8208 33.2876 18.8208H31.8612C31.6401 18.8208 31.5171 18.6981 31.5171 18.4775Z" fill="currentColor" />
            <path d="M37.2734 18.4788V10.2825C37.2734 7.95079 38.8228 6.28229 41.1102 6.28229C43.3976 6.28229 44.9469 7.95079 44.9469 10.2825V18.4788C44.9469 18.6994 44.8239 18.8221 44.6028 18.8221H43.1764C42.9553 18.8221 42.8323 18.6994 42.8323 18.4788V10.2081C42.8323 9.05528 42.1924 8.26934 41.1102 8.26934C40.028 8.26934 39.3881 9.05528 39.3881 10.2081V18.4788C39.3881 18.6994 39.2651 18.8221 39.0439 18.8221H37.6176C37.3964 18.8221 37.2734 18.6994 37.2734 18.4788Z" fill="currentColor" />
            <path d="M56.1379 10.2812V19.9997C56.1379 22.4294 54.6134 24 52.2266 24C49.8397 24 48.4147 22.4542 48.3152 20.0494C48.2904 19.8287 48.4134 19.706 48.6345 19.706H50.0857C50.282 19.706 50.405 19.8039 50.4299 20.0494C50.5032 21.2766 51.1679 21.9881 52.2253 21.9881C53.3572 21.9881 54.0206 21.2766 54.0206 20.0742V17.6445C53.5037 18.185 52.8154 18.5036 52.0525 18.5036C49.8633 18.5036 48.2891 16.8351 48.2891 14.5034V10.2825C48.2891 7.95079 49.8633 6.28229 52.2253 6.28229C54.5872 6.28229 56.1366 7.95079 56.1366 10.2825L56.1379 10.2812ZM54.0232 14.5765V10.2081C54.0232 9.05528 53.3598 8.29415 52.2279 8.29415C51.096 8.29415 50.4076 9.05528 50.4076 10.2081V14.5765C50.4076 15.7293 51.096 16.5152 52.2279 16.5152C53.3598 16.5152 54.0232 15.7293 54.0232 14.5765Z" fill="currentColor" />
            <path d="M61.3278 6.55127C61.5489 6.55127 61.6719 6.67399 61.6719 6.89463V15.1653C61.6719 16.3181 62.3105 17.1041 63.394 17.1041C64.4775 17.1041 65.1161 16.3181 65.1161 15.1653V6.89463C65.1161 6.67399 65.2391 6.55127 65.4602 6.55127H66.8866C67.1077 6.55127 67.2307 6.67399 67.2307 6.89463V15.0909C67.2307 17.4226 65.6814 19.0911 63.394 19.0911C61.1066 19.0911 59.5573 17.4226 59.5573 15.0909V6.89463C59.5573 6.67399 59.6803 6.55127 59.9014 6.55127H61.3278Z" fill="currentColor" />
            <path d="M70.8712 3.75343C70.8712 3.0171 71.3881 2.47791 72.1261 2.47791C72.8642 2.47791 73.4046 3.0184 73.4046 3.75343C73.4046 4.41665 72.8393 5.00546 72.1261 5.00546C71.413 5.00546 70.8712 4.46496 70.8712 3.75343ZM71.0688 18.4775V6.86979C71.0688 6.67396 71.1918 6.55123 71.413 6.55123H72.8393C73.0618 6.55123 73.1835 6.67396 73.1835 6.86979V18.4775C73.1835 18.6981 73.0605 18.8208 72.8393 18.8208H71.413C71.1918 18.8208 71.0688 18.6981 71.0688 18.4775Z" fill="currentColor" />
            <path d="M76.5282 15.1901C76.5282 14.993 76.6028 14.8219 76.8723 14.8219H78.2987C78.4714 14.8219 78.6193 14.8951 78.6428 15.1405C78.741 16.3925 79.3311 17.0792 80.6109 17.0792C81.7428 17.0792 82.333 16.4408 82.333 15.5583C82.333 13.0803 76.6747 13.7422 76.6747 9.71722C76.6747 7.75367 78.1757 6.28101 80.3885 6.28101C83.02 6.28101 84.1768 7.97431 84.2501 9.88825C84.2501 10.0606 84.1755 10.2316 83.9295 10.2316H82.4783C82.282 10.2316 82.159 10.1089 82.1341 9.91306C82.0608 8.95609 81.4955 8.26806 80.4369 8.26806C79.3782 8.26806 78.7645 8.88167 78.7645 9.76553C78.7645 12.1221 84.4464 11.4588 84.4464 15.5818C84.4464 17.5701 82.9219 19.0911 80.6345 19.0911C78.0762 19.0911 76.6001 17.3482 76.5269 15.1888L76.5282 15.1901Z" fill="currentColor" />
            <path d="M87.1538 15.0674V10.2577C87.1787 7.92599 88.7032 6.3071 91.0651 6.3071C93.3774 6.2823 95.0013 7.92729 95.0013 10.1846C95.0013 12.4419 93.4271 13.8898 91.09 13.8898H89.2698V15.1653C89.2698 16.2946 89.9332 17.0792 91.0651 17.0792C92.074 17.0792 92.7388 16.3429 92.8121 15.2632C92.837 15.0426 92.96 14.9446 93.1562 14.9446H94.6075C94.805 14.9446 94.9281 15.0674 94.9281 15.2632C94.805 17.5453 93.2544 19.0663 91.0416 19.0663C88.8288 19.0663 87.1551 17.4709 87.1551 15.0661L87.1538 15.0674ZM91.09 11.9745C92.2219 11.9745 92.8854 11.263 92.8854 10.2316C92.8854 9.05399 92.1971 8.29285 91.0651 8.29285C89.9332 8.29285 89.2698 9.05399 89.2698 10.2316V11.9745H91.09Z" fill="currentColor" />
            <path d="M11.8361 13.9015C11.505 13.4981 11.0156 13.267 10.4922 13.267H7.50729L8.82241 6.73144C9.1561 5.07208 8.73212 3.36963 7.65647 2.06016C6.58344 0.750694 4.99352 0 3.2976 0C3.07122 0 2.87493 0.160583 2.83044 0.381222L0.167488 13.613C0.163562 13.6248 0.159636 13.6378 0.157019 13.6483L0.108602 13.8898C-0.218542 15.5178 0.198893 17.1876 1.25361 18.4723C2.30832 19.7556 3.86814 20.4933 5.53265 20.4933H7.26913L6.76664 22.9921C6.72607 23.1906 6.81767 23.3929 6.99564 23.4974C7.06761 23.5391 7.15005 23.5613 7.2338 23.5613C7.34895 23.5613 7.45887 23.5196 7.54655 23.4438L11.0823 20.3784C11.0928 20.3693 11.1019 20.3601 11.1111 20.3484C11.1635 20.2948 11.2014 20.2309 11.2145 20.18C11.2171 20.1734 11.221 20.1656 11.2236 20.1578C11.2302 20.1434 11.2341 20.1264 11.238 20.1121L12.1959 15.3403C12.2993 14.8285 12.1671 14.305 11.8361 13.9015ZM9.49763 20.4933L7.97576 21.8132L8.2414 20.4933H9.49763ZM7.8907 6.54344L6.53764 13.2657H1.20781L3.68494 0.96611C4.9451 1.06925 6.11627 1.68025 6.92105 2.66202C7.8135 3.74955 8.16681 5.16477 7.88939 6.54344H7.8907ZM5.43581 19.5376C5.42273 19.535 5.40833 19.5324 5.39394 19.5311C4.08929 19.4567 2.86708 18.8378 2.04006 17.8313C1.20781 16.8181 0.84533 15.51 1.02853 14.2162H6.58867C6.60437 14.2188 6.62008 14.2214 6.63578 14.2214H7.12126C7.13696 14.2214 7.15267 14.2201 7.16706 14.2162H10.4935C10.729 14.2162 10.9515 14.3206 11.1006 14.5034C11.2498 14.6862 11.31 14.9238 11.2629 15.1536L10.3809 19.5441H5.99588C5.9828 19.5415 5.9684 19.5402 5.95401 19.5402H5.43451V19.5389L5.43581 19.5376Z" fill="currentColor" />
        </g>
        <defs>
            <clipPath id="clip0_101_1396">
                <rect width="95" height="24" fill="white"/>
            </clipPath>
        </defs>
    </svg>
</div>

<?php if ($has_oobe_already) : ?>
<!-- login page -->
<?php require_once __DIR__ . DIRECTORY_SEPARATOR . 'login.php' ?>
<?php else : ?>
<!-- oobe page -->
<form id="register-page" action="" method="post" class="linguise-form-container">
    <input type="hidden" name="_token" value="<?php echo esc_attr(Session::getInstance()->getCsrfToken('linguise_oobe_register')); ?>" />
    <input type="hidden" name="linguise_action" value="activate-linguise" />
    <div class="linguise-form-area flex flex-col gap-2">
        <div>
            <h3 class="m-0 text-base text-neutral-deep font-semibold">
                <?php echo esc_html($translation_strings_root['header']['title']); ?>
                <span
                    class="material-icons help-tooltip"
                    data-tippy="<?php echo esc_attr($translation_strings_root['header']['help']) ?>"
                >
                    help_outline
                </span>
            </h3>
            <p class="text-base text-neutral-deep font-italic">
                <?php echo esc_html($translation_strings_root['header']['subtitle']); ?>
            </p>
        </div>
        <?php if ($has_token_already) : ?>
        <div>
            <label for="token-box" class="text-base text-neutral">
                <?php echo esc_html($translation_strings_root['token']['label']); ?>
                <span
                    class="material-icons help-tooltip"
                    data-tippy="<?php echo esc_attr($translation_strings_root['token']['help']) ?>"
                >
                    help_outline
                </span>
            </label>
            <input type="text" id="token-box" name="token" placeholder="<?php echo esc_attr($translation_strings_root['token']['placeholder']); ?>" class="linguise-input rounder mt-2 w-full" required />
        </div>
        <?php endif; ?>
        <div>
            <label for="password-box" class="text-base text-neutral">
                <?php echo esc_html($translation_strings_root['password']['label']); ?>
            </label>
            <input type="password" id="password-box" name="password" placeholder="<?php echo esc_attr($translation_strings_root['password']['placeholder']); ?>" class="linguise-input rounder mt-2 w-full" required minlength="10" autocomplete="new-password" />
        </div>
    </div>
    <div id="db-container" class="linguise-form-area flex flex-col gap-2">
        <div>
            <label for="database-box" class="m-0 text-base text-neutral-deep font-semibold">
                <?php echo esc_html($translation_strings_root['database']['label']); ?>
                <span
                    class="material-icons help-tooltip"
                    data-tippy="<?php echo esc_attr($translation_strings_root['database']['help']) ?>"
                >
                    help_outline
                </span>
            </label>
            <select id="database-box" class="linguise-input rounder mt-2" name="db_mode" <?php echo $force_only_mysql ? 'disabled' : ''; ?> data-always-disable="<?php echo $force_only_mysql ? '1' : '0'; ?>">
            <?php foreach ($database_modes as $db_mode) : ?>
                <option value="<?php echo esc_attr($db_mode['value']); ?>" <?php if (isset($db_mode['selected']) && $db_mode['selected']) : ?>selected<?php endif; ?>>
                    <?php echo esc_html($db_mode['label']); ?>
                </option>
            <?php endforeach; ?>
            </select>
        </div>
        <div class="flex flex-row items-center align-center gap-2 mysql-marker" <?php if (!$force_only_mysql) : ?>style="display: none;"<?php endif; ?>>
            <div class="w-full">
                <label for="mysql-host-box" class="text-base text-neutral">
                    <?php echo esc_html($translation_strings_root['mysql']['host']['label']); ?>
                </label>
                <input type="text" id="mysql-host-box" name="db_host" placeholder="<?php echo esc_attr($translation_strings_root['mysql']['host']['placeholder']); ?>" class="linguise-input rounder mt-2 w-full" required />
            </div>
            <div class="mw-fit-content">
                <label for="mysql-port-box" class="text-base text-neutral">
                    <?php echo esc_html($translation_strings_root['mysql']['port']['label']); ?>
                </label>
                <input type="number" id="mysql-port-box" name="db_port" placeholder="<?php echo esc_attr($translation_strings_root['mysql']['port']['placeholder']); ?>" class="linguise-input rounder mt-2 w-full" value="3306" style="width: 6rem;" />
            </div>
        </div>
        <div class="w-full mysql-marker" <?php if (!$force_only_mysql) : ?>style="display: none;"<?php endif; ?>>
            <label for="mysql-user-box" class="text-base text-neutral">
                <?php echo esc_html($translation_strings_root['mysql']['user']['label']); ?>
            </label>
            <input type="text" id="mysql-user-box" name="db_user" placeholder="<?php echo esc_attr($translation_strings_root['mysql']['user']['placeholder']); ?>" class="linguise-input rounder mt-2 w-full" required />
        </div>
        <div class="w-full mysql-marker" <?php if (!$force_only_mysql) : ?>style="display: none;"<?php endif; ?>>
            <label for="mysql-pass-box" class="text-base text-neutral">
                <?php echo esc_html($translation_strings_root['mysql']['password']['label']); ?>
            </label>
            <input type="password" id="mysql-pass-box" name="db_password" placeholder="<?php echo esc_attr($translation_strings_root['mysql']['password']['placeholder']); ?>" class="linguise-input rounder mt-2 w-full" />
        </div>
        <div class="flex flex-row items-center align-center gap-2 mysql-marker" <?php if (!$force_only_mysql) : ?>style="display: none;"<?php endif; ?>>
            <div class="w-full">
                <label for="mysql-name-box" class="text-base text-neutral">
                    <?php echo esc_html($translation_strings_root['mysql']['name']['label']); ?>
                </label>
                <input type="text" id="mysql-name-box" name="db_name" placeholder="<?php echo esc_attr($translation_strings_root['mysql']['name']['placeholder']); ?>" class="linguise-input rounder mt-2 w-full" required />
            </div>
            <div class="w-full">
                <label for="mysql-prefix-box" class="text-base text-neutral">
                    <?php echo esc_html($translation_strings_root['mysql']['prefix']['label']); ?>
                </label>
                <input type="text" id="mysql-prefix-box" name="db_prefix" placeholder="<?php echo esc_attr($translation_strings_root['mysql']['prefix']['placeholder']); ?>" class="linguise-input rounder mt-2 w-full" required value="ling_" />
            </div>
        </div>
    </div>
    <div class="submit-container">
        <button type="button" class="linguise-btn rounder" data-action="test-connection"><?php echo esc_html($translation_strings_root['register']['test']); ?></button>
        <input type="submit" class="linguise-btn rounder" value="<?php echo esc_attr($translation_strings_root['register']['label']); ?>" data-action="register" style="display: none;" />
    </div>
</form>
<?php endif; ?>

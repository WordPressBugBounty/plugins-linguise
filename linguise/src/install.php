<?php
add_action('admin_init', function () {
    $installed_version = get_option('linguise_version', null);

    if (!$installed_version) {
        define('LINGUISE_SCRIPT_TRANSLATION', true);
        require_once(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'linguise' . DIRECTORY_SEPARATOR . 'script-php' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Databases' . DIRECTORY_SEPARATOR . 'Mysql.php');

        global $wpdb;
        $mysql_instance = \Linguise\Vendor\Linguise\Script\Core\Databases\Mysql::getInstance();
        $install_query = $mysql_instance->getInstallQuery($wpdb->base_prefix . 'linguise_urls');
        $wpdb->query($install_query);
    } else {
        // This is an update
        if (version_compare($installed_version, '1.7.6') === -1) {
            // Do not add flag on already installed versions
            $linguise_options = get_option('linguise_options');
            $linguise_options['add_flag_automatically'] = 0;
            update_option('linguise_options', $linguise_options);
        }
    }
    $installed_at = get_option('linguise_install_time', false);
    if ($installed_at !== false && (int) $installed_at + (60 * DAY_IN_SECONDS) < time()) {
        // Check dismiss flag and showing feedback notification
        $dismissed = get_option('linguise_feedback_notify_dismissed', false);

        if (false === $dismissed) {
            // Show notification
            add_action('admin_notices', 'linguise_feedback_notice');
        }
    }

    if ($installed_version !== LINGUISE_VERSION) {
        update_option('linguise_version', LINGUISE_VERSION);
    }
});

/**
 * Displays a notice on WordPress backend
 *
 * @return void
 */
function linguise_feedback_notice()
{
    ?>
    <style>
        #linguise_admin_notice {
            display: flex;
            align-items: center;
        }
        #linguise_admin_notice img {
            margin-right: 20px;
        }
        @media only screen and (max-width: 767px) {
            #linguise_admin_notice {
                flex-direction: column;
            }
            #linguise_admin_notice .linguise_feedback_content {
                text-align: center;
            }
        }
    </style>
    <div id="linguise_admin_notice" class="notice notice-success is-dismissible">
        <img src="data:image/webp;base64,UklGRqYDAABXRUJQVlA4TJkDAAAvx8AOEF8wRuM1vgraRkLxS2CGABzhWDwQjMOjUEBQmrZtbd08loxJQeWGVeZWM+GUvCrDWZVBYcYtM2lzTleZUZnbsPnz/fP0SZZ0XFxF9H8C9P/v9qrKX0CHyJ3k1IV1U6d3gagyQZfdKZ5TFt8palDbOF6UW++KUw5vFFUKGrn6Ks7Lh1Y5QqgVhOSN6zkFsLAcJ6BZjB9L6i7OyYcF5QihVkz7sKQTb+ekwRW3HJ6JKsWEn0pqH87Lv0cl9R0VO/uipCDKa2reXChJhQXb1lRS/C2vOQVs3vZIir95jZOh25Lk5zaingjHsKtiCQxEbqLLAskfX3Ekf3zFkeT9CjxpCUYQu2n9hqSggKYkzwAssgwB3k4MWCgF4EoBuJLmSFYTPYDDacOapHB3MSdIxokQq5uTsTwjKcBaTzGupBMvFtODS2OoS+pAbGBxPm3gMkSSfoDYwIspSNL3S4ox7NYMtCQNiZ3A8HY+J+AB/QSO1CN2fMNhWxAnOq1CPGhKQ56RZFgkdYjyuUkkzUBVGvGMNMcV28zhRLdZSACu1OVFSVCXThDntVvyoC4ZmlIItgNvJwb1Etxid1oIeR2WdOGCK0E90+wziZFbgpslOAFV2Se5uTAxrkyFGTjsZJrjiq3blOTHmgoycNjNEBgO2/p1SUE0JeaAyLVZn7ENq5LC3VPCGwJRJUvVZkiN3SkgfwgsyvCW7GR8cRrI/w3itJ1OSqzUA7ungrwhVBN1ZfQztKdAsNKV2tCcSKSFn5boRF5dnpFkaKXMcMVm3JQTL2boEOdnWCR1LH2ekdoTvC1pxELJ0JROgG3QTLm5MMMsNHIbEjuBsXTZLZ0Apd0ikmRoSiOWSD8Q2TovpnRaGUKIVq5c6eTSgdhg+Z4rrvrEGWahoQPQkPrEjmf41BZecWy9RgZvTLKZS4hd0gGIL8PbGdpw5cIYXOkHiEaw2OaZt2yDWgZ1C9Awgzcm2cqgEclI0gzWmk1zvOEkRm6W9riAwMB2i35MxJUsc5ZFkjRMHFb6r9iV+dA4rSH54CTeTsjf8mho80fA/ZLkgyNJvwK7lAwMxG4G7+w4EWWTv3LlSke5p8jfuu1RTbh525qKRcG2ba4mHLzGgxPkv2VbJUvZR6PDKinU58+YWnka0gni+eDzlspqWCx1iOZDcMUpzYDY8Q2fzgc9otJ2IDaweF6UOMRam24aJg5rygcGYnfayd/6gaP/BQMA" width="200" height="60" />
        <div class="linguise_feedback_content">
            <p>
                <?php esc_html_e('You\'re using Linguise for 2 months now. We hope you enjoyed our service. If you have 2 minutes to help us to grow, please drop us a quick review. We\'d really appreciate.', 'linguise'); ?>
                <br />
                <?php esc_html_e('Thanks again for using Linguise', 'linguise'); ?>
            </p>
            <p class="linguise_notice_buttons">
                <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin-ajax.php?action=linguise_feedback_dismiss'), '_linguise_feedback_nonce_')); ?>" class="button" id="linguise_feedback_notice_dismiss" ><?php esc_html_e('I already left a review', 'linguise') ?></a>
                <a target="_blank" class="button button-primary" href="https://wordpress.org/support/plugin/linguise/reviews/?filter=5#new-post" title="<?php esc_html_e('Leave a review', 'linguise') ?>"><?php esc_html_e('Leave a review', 'linguise') ?></a>
            </p>
        </div>

    </div>
    <script type="text/javascript">
        jQuery(function($) {
          $(document).ready(function() {
            $(document).on('click', '#linguise_admin_notice .notice-dismiss', function(e) {
              e.preventDefault();
              $('#linguise_feedback_notice_dismiss').trigger('click');
              return false;
            }).on('click', '#linguise_feedback_notice_dismiss', function(e) {
              e.preventDefault();
              // Send ajax to dismiss URL
              $.ajax({
                url: $(this).prop('href'),
                method: 'GET',
                success: function(data) {
                  $('#linguise_admin_notice').fadeOut('fast', function() {
                    $(this).remove();
                  })
                }
              });
              return false;
            });
          });
        });
    </script>
    <?php
}

add_action('wp_ajax_linguise_feedback_dismiss', function () {
    check_admin_referer('_linguise_feedback_nonce_');
    // check user capabilities
    if (!current_user_can('manage_options')) {
        header('Content-Type: application/json; charset=UTF-8;');
        echo json_encode(['success' => false]);
        die();
    }

    update_option('linguise_feedback_notify_dismissed', true);
    wp_send_json_success();
});

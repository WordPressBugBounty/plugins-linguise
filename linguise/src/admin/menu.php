<?php
/* Prohibit direct script loading */
defined('ABSPATH') || die('No direct script access allowed!');

/**
 * Class LinguiseMenu
 */
class LinguiseMenu
{
    /**
     * LinguiseMenu constructor.
     */
    public function __construct()
    {
        add_action('admin_init', array($this, 'adminInit'));
    }

    /**
     * Setups filters and terms
     * adds the language switcher metabox and create new nav menu locations
     *
     * @return void
     * @since  1.1
     */
    public function adminInit()
    {
        add_action('wp_update_nav_menu_item', array($this, 'wpUpdateNavMenuItem'), 10, 2);
        add_meta_box('linguise_lang_switch_box', __('Linguise switcher', 'linguise'), array($this, 'langSwitch'), 'nav-menus', 'side', 'high');
        add_filter('get_user_option_metaboxhidden_nav-menus', array($this, 'enableMenuMetaBox'), 10, 3);
    }

    /**
     * Enable menu meta box
     *
     * @param mixed   $result Value for the user's option.
     * @param string  $option Name of the option being retrieved.
     * @param WP_User $user   WP_User object of the user whose option is being retrieved.
     *
     * @return mixed
     */
    public function enableMenuMetaBox($result, $option, $user)
    {
        if (is_array($result) && in_array('linguise_lang_switch_box', $result)) {
            $key = array_search('linguise_lang_switch_box', $result);
            unset($result[$key]);
        }
        return $result;
    }

    /**
     * Language switcher metabox
     * The checkbox and all hidden fields are important
     * Thanks to John Morris for his very interesting post http://www.johnmorrisonline.com/how-to-add-a-fully-functional-custom-meta-box-to-wordpress-navigation-menus/
     *
     * @return void
     * @since  1.1
     */
    public function langSwitch()
    {
        global $_nav_menu_placeholder, $nav_menu_selected_id;
        $_nav_menu_placeholder = 0 > $_nav_menu_placeholder ? $_nav_menu_placeholder - 1 : -1; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Force it
        ?>
        <div id="posttype-lang-switch" class="posttypediv">
            <div id="tabs-panel-lang-switch" class="tabs-panel tabs-panel-active">
                <ul id="lang-switch-checklist" class="categorychecklist form-no-clear">
                    <li>
                        <label class="menu-item-title">
                            <input type="checkbox" class="menu-item-checkbox"
                                   name="menu-item[<?php echo (int)$_nav_menu_placeholder; ?>][menu-item-object-id]"
                                   value="-1"> <?php esc_html_e('Linguise Languages', 'linguise'); ?>
                        </label>
                        <input type="hidden" class="menu-item-type"
                               name="menu-item[<?php echo (int)$_nav_menu_placeholder; ?>][menu-item-type]"
                               value="custom">
                        <input type="hidden" class="menu-item-title"
                               name="menu-item[<?php echo (int)$_nav_menu_placeholder; ?>][menu-item-title]"
                               value="<?php esc_html_e('Linguise Languages', 'linguise'); ?>">
                        <input type="hidden" class="menu-item-url"
                               name="menu-item[<?php echo (int)$_nav_menu_placeholder; ?>][menu-item-url]"
                               value="#linguise_switcher">
                    </li>
                </ul>
            </div>
            <p class="button-controls">
                <span class="add-to-menu">
                    <input type="submit" <?php disabled($nav_menu_selected_id, 0); ?> class="button-secondary submit-add-to-menu right"
                           value="<?php esc_attr_e('Add to Menu', 'linguise'); ?>" name="add-post-type-menu-item"
                           id="submit-posttype-lang-switch">
                    <span class="spinner"></span>
                </span>
            </p>
        </div>
        <?php
    }

    /**
     * Save our menu item options
     *
     * @param integer $menu_id         Not used
     * @param integer $menu_item_db_id Menu item db Id
     *
     * @return void
     */
    public function wpUpdateNavMenuItem($menu_id = 0, $menu_item_db_id = 0)
    {
        if (empty($_POST['menu-item-url'][$menu_item_db_id]) || '#linguise_switcher' !== $_POST['menu-item-url'][$menu_item_db_id]) { // phpcs:ignore WordPress.Security.NonceVerification
            return;
        }

        // Security check as 'wpUpdateNavMenuItem' can be called from outside WP admin
        if (current_user_can('edit_theme_options')) {
            check_admin_referer('update-nav_menu', 'update-nav-menu-nonce');

            $options = linguiseGetOptions();
            // Our jQuery form has not been displayed
            if (empty($_POST['menu-item-linguise-detect'][$menu_item_db_id])) {
                if (!get_post_meta($menu_item_db_id, '_linguise_menu_item', true)) { // Our options were never saved
                    update_post_meta($menu_item_db_id, '_linguise_menu_item', $options);
                }
            }
        }
    }
}

new LinguiseMenu;

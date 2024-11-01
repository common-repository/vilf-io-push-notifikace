<?php

defined('ABSPATH') or die('This page may not be accessed directly.');


class VilfIoAdmin
{
    public static $SAVE_CONFIG_NONCE_KEY = 'vilfio_config_page_nonce';
    public static $SAVE_CONFIG_NONCE_ACTION = 'vilfio_config_page';
    private static $SAVE_POST_NONCE_KEY = 'vilfio_meta_box_nonce';
    private static $SAVE_POST_NONCE_ACTION = 'vilfio_meta_box';

    private static $KEY_TITLE = 'vilfio_title_notification';
    private static $KEY_MESSAGE = 'vilfio_message_notification';
    private static $KEY_SEND_NOTIFICATION = 'send_vilfio_notification';
    private static $KEY_SENT_NOTIFICATION = 'vilfio_notification_already_sent';
    private static $KEY_META_BOX_PRESENT = 'vilfio_meta_box_present';

    public static function init()
    {
        $vilfio = new self();
        if (VilfIoUtils::can_modify_plugin_settings()) {
            add_action('admin_menu', array(__CLASS__, 'addSettingPage'));
        }
        $setting = VilfIo::loadSetting();
        if ($setting['tags_cart']) {
            add_action('woocommerce_order_status_changed', array(__CLASS__, 'onChangeOrderStatus'), 10, 4);
        }
        add_action('save_post', array(__CLASS__, 'onSavePost'), 1, 3);
        add_action('transition_post_status', array(__CLASS__, 'onTransitionPostStatus'), 10, 3);

        if (VilfIoUtils::can_send_notifications()) {
            add_action('admin_init', array(__CLASS__, 'addVilfIoPostOptions'));
        }
        return $vilfio;
    }

    /**
     * Save the meta when the post is saved.
     *
     * @param int $post_id the ID of the post being saved
     * @return int|void
     */
    public static function onSavePost($post_id, $post, $updated)
    {
        if ($post->post_type === 'wdslp-wds-log') {
            // Prevent recursive post logging
            return;
        }

        // Verify that the nonce is valid.
        if (!wp_verify_nonce(isset($_REQUEST[VilfIoAdmin::$SAVE_POST_NONCE_KEY]) ? $_POST[VilfIoAdmin::$SAVE_POST_NONCE_KEY] : '', VilfIoAdmin::$SAVE_POST_NONCE_ACTION)) {
            return $post_id;
        }

        // If this is an autosave, our form has not been submitted, so we don't want to do anything.
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return $post_id;
        }


        if (array_key_exists(self::$KEY_META_BOX_PRESENT, $_POST) && array_key_exists(self::$KEY_SEND_NOTIFICATION, $_POST)) {
            // notification will be save
            update_post_meta($post_id, self::$KEY_SEND_NOTIFICATION, true);
            update_post_meta($post_id, self::$KEY_TITLE, sanitize_text_field($_POST[self::$KEY_TITLE]));
            update_post_meta($post_id, self::$KEY_MESSAGE, sanitize_text_field($_POST[self::$KEY_MESSAGE]));
        } else {
            update_post_meta($post_id, self::$KEY_SEND_NOTIFICATION, false);
        }

        $just_sent_notification = (get_post_meta($post_id, self::$KEY_SENT_NOTIFICATION) === true);
        if ($just_sent_notification) {
            // Reset our flag
            update_post_meta($post_id, self::$KEY_SENT_NOTIFICATION, false);

            return;
        }
    }

    public static function onChangeOrderStatus($thisGetId, $thisStatusTransitionFrom, $thisStatusTransitionTo, $instance)
    {
        $setting = VilfIo::loadSetting();
        $body = array(
            'order_status' => $thisStatusTransitionTo,
            'order_id' => $thisGetId,
        );
        $args = array(
            'body' => wp_json_encode($body),
            'timeout' => '5',
            'redirection' => '5',
            'httpversion' => '1.0',
            'blocking' => true,
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => $setting['api_token']
            ],
            'cookies' => array()
        );
        $url = VILFIO_DEV ? "https://vilfiodev.eu/rest-api/update-order" : "https://vilf.io/rest-api/update-order";
        $response = wp_remote_post($url, $args);
    }

    /**
     * Add box to post
     */
    public static function addVilfIoPostOptions()
    {
        add_meta_box('vilfio_notif_on_post',
            'Vilf.io - push notifikace',
            array(__CLASS__, 'vilfIoRenderBlogPostNotificationBox'),
            'post',
            'side',
            'high');
    }

    /**
     * Render Meta Box content.
     *
     * @param WP_Post $post the post object
     */
    public static function vilfIoRenderBlogPostNotificationBox($post)
    {
        if (get_post_meta('post', $post->ID, self::$KEY_SENT_NOTIFICATION)) {
            // notification already sent we do not care about this post any more
            return;
        }

        $post_type = $post->post_type;

        // Add an nonce field so we can check for it later.
        wp_nonce_field(VilfIoAdmin::$SAVE_POST_NONCE_ACTION, VilfIoAdmin::$SAVE_POST_NONCE_KEY, true);
        ?>

        <input type="hidden" name="<?php echo self::$KEY_META_BOX_PRESENT; ?>" value="true" checked/>
        <input type="checkbox" name="<?php echo self::$KEY_SEND_NOTIFICATION; ?>"/>
        <label>
            Odeslat notifikaci po publikování
        </label>
        <div style="margin-top: 15px">
            <label>Titulek notifikace</label>
        </div>
        <div>
            <input type="text" name="<?php echo self::$KEY_TITLE ?>" style="width: 100%;"/>
        </div>
        <div style="margin-top: 5px">
            <label>Zpráva notifikace</label>
        </div>
        <div>
            <input type="text" name="<?php echo self::$KEY_MESSAGE ?>" style="width: 100%;"/>
        </div>
        <?php
    }

    /**
     * Add plugin setting to menu
     */
    public static function addSettingPage()
    {
        if (VilfIoUtils::can_modify_plugin_settings()) {
            $VilfIo_menu = add_menu_page('Vilf.io nastavení',
                'Vilf.io nastavení',
                'manage_options',
                'vilfio-push',
                array(__CLASS__, 'settingMenu')
            );

            VilfIoAdmin::saveSettingForm();

            add_action('load-' . $VilfIo_menu, array(__CLASS__, 'settingLoad'));
        }
    }

    /**
     * Save form plugin setting
     */
    public static function saveSettingForm()
    {

        if (array_key_exists('app_id', $_POST)) {
            // check_admin_referer dies if not valid; no if statement necessary
            check_admin_referer(VilfIoAdmin::$SAVE_CONFIG_NONCE_ACTION, VilfIoAdmin::$SAVE_CONFIG_NONCE_KEY);

            if (!VilfIoUtils::can_modify_plugin_settings()) {
                // User can not update plugin setting
                set_transient('vilfio_transient_error', '<div class="error notice">
                    <p><strong>VilfIo Push:</strong><em> Pouze administrátor může změnit nastavení pluginu.</em></p>
                </div>', 90000);
                return;
            }
            $settings = VilfIo::loadSetting();

            // save string setting
            $stringSettings = array(
                'api_token',
                'icon_url',
            );
            foreach ($stringSettings as $setting) {
                //exist and is valid string
                if (array_key_exists($setting, $_POST) && is_string($_POST[$setting])) {
                    // sanitize text
                    $value = $_POST[$setting];
                    $value = sanitize_text_field($value);
                    $settings[$setting] = $value;
                }
            }

            // save number setting
            $stringSettings = array(
                'app_id',
            );
            foreach ($stringSettings as $setting) {
                if (array_key_exists($setting, $_POST)) {
                    $value = $_POST[$setting];
                    // sanitize
                    $value = sanitize_text_field($value);
                    //save only number save
                    if (preg_match('/^[0-9]{0,}$/', $value)) {
                        $settings[$setting] = $value;
                    }
                }
            }

            //save booleans values
            $booleanSettings = array(
                'tags_name',
                'tags_cart',
                'tags_order',
            );
            foreach ($booleanSettings as $setting) {
                if (array_key_exists($setting, $_POST)) {
                    $settings[$setting] = true;
                } else {
                    $settings[$setting] = false;
                }
            }

            VilfIo::saveVilfIoSettings($settings);
        }
    }

    /**
     * Render setting page
     */
    public static function settingMenu()
    {
        require_once plugin_dir_path(__FILE__) . '/setting.php';
    }

    /**
     * Load plugin setting
     */
    public static function settingLoad()
    {

        $setting = VilfIo::loadSetting();
        if (
            $setting['app_id'] === '' || $setting['api_token'] === ''
        ) {
            function settingsSetUpWarningNotComplete()
            {
                //print error to page
                ?>
                <div class="error notice">
                    <p><strong>Vilf.Io Push:</strong> <em>Nastavení pluginu není kompletní. Prosím doplňte App ID a Api
                            token, jenž získáte v administraci Vilf.io.</em>
                    </p>
                </div>
                <?php
            }

            add_action('admin_notices', 'settingsSetUpWarningNotComplete');
        }
    }

    public static function onTransitionPostStatus($newStatus, $oldStatus, $post)
    {
        if ($post->post_type === 'wdslp-wds-log' ||
            $oldStatus === 'trash' && $newStatus === 'publish') {
            // It's important not to call vilfio_debug() on posts of type wdslp-wds-log, otherwise each post will recursively generate 4 more posts
            return;
        }
        if (!(
            $newStatus === 'publish' &&
            $post->post_type === 'post')) {
            return;
        }
        if (get_post_meta($post->ID, self::$KEY_SENT_NOTIFICATION, true)) {
            //notification already sent
            return;
        }

        // quirk of Gutenberg editor leads to two passes if meta box is added
        // conditional removes first pass
        if (defined('REST_REQUEST') && REST_REQUEST) {
            return;
        }

        $setting = VilfIo::loadSetting();

        // find images
        $icon = $setting['icon_url'];
        $thumbnail_image = get_the_post_thumbnail_url($post->ID, 'large');

        if (metadata_exists('post', $post->ID, self::$KEY_TITLE)) {
            if (!get_post_meta($post->ID, self::$KEY_SEND_NOTIFICATION, true)) {
                // user do not wont to send notification!
                return;
            }

            $body = array(
                'segment_id' => 'system-all',
                'send_option' => [
                    'title' => get_post_meta($post->ID, self::$KEY_TITLE, true),
                    'body' => get_post_meta($post->ID, self::$KEY_MESSAGE, true),
                    'icon' => $icon ? $icon : null,
                    'image' => $thumbnail_image ? $thumbnail_image : null,
                    'badge' => null,
                    'link' => get_permalink($post->ID),
                ],
                'date' => current_time('Y-m-d\TH:i:sO'),
            );
        } else if (isset($_POST[self::$KEY_TITLE])) {
            // when user create new post metadata will not be already saved in DB

            if (!array_key_exists(self::$KEY_SEND_NOTIFICATION, $_POST)) {
                // user do not wont to send notification!
                return;
            }

            $body = array(
                'segment_id' => 'system-all',
                'send_option' => [
                    'title' => $_POST[self::$KEY_TITLE],
                    'body' => $_POST[self::$KEY_MESSAGE],
                    'icon' => $icon ? $icon : null,
                    'image' => $thumbnail_image ? $thumbnail_image : null,
                    'badge' => null,
                    'link' => get_permalink($post->ID),
                ],
                'date' => current_time('Y-m-d\TH:i:sO'),
            );
        } else {
            // No data :(
            return;
        }
        $args = array(
            'body' => wp_json_encode($body),
            'timeout' => '5',
            'redirection' => '5',
            'httpversion' => '1.0',
            'blocking' => true,
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => $setting['api_token']
            ],
            'cookies' => array()
        );
        $url = VILFIO_DEV ? "https://vilfiodev.eu/rest-api/push-messages" : "https://vilf.io/rest-api/push-messages";
        $response = wp_remote_post($url, $args);
        update_post_meta($post->ID, self::$KEY_SENT_NOTIFICATION, true);
    }
}
?>

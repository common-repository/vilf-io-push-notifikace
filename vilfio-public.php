<?php

defined('ABSPATH') or die('This page may not be accessed directly.');

/**
 * This ca create async load script
 *
 * @param $url
 * @return string
 */
function addAsyncForScript($url)
{
    if (strpos($url, '#asyncload') === false)
        return $url;
    return str_replace('#asyncload', '', $url) . "' async='async";
}

class VilfIoPublic
{
    public function __construct()
    {
    }

    public static function init()
    {
        add_action('wp_head', array(__CLASS__, 'vilfIoHeaders'), 10);
        add_filter('woocommerce_add_to_cart_fragments', array(__CLASS__, 'updateCartContent'), 10);
        add_action('woocommerce_thankyou', array(__CLASS__, 'jsTrackingThankYouPage'), 90, 1);
    }

    /**
     * Print to page cart content when is updated by ajax.
     *
     * @param $fragments
     * @return mixed
     */
    public static function updateCartContent($fragments)
    {
        $fragments['noscript#updated-cart-content'] = '<noscript id="updated-cart-content" style="display: none" ">' . json_encode(self::getCartContent()) . '</noscript>';
        return $fragments;
    }

    /**
     * Add JS to run Vilf.io
     */
    public static function vilfIoHeaders()
    {
        $setting = VilfIo::loadSetting();

        add_filter('clean_url', 'addAsyncForScript', 11, 1);
        if (VILFIO_DEV) {
            wp_enqueue_script('vilfIoApi', "https://vilfiodev.eu/api/api.js#asyncload", array(), null, true);
            wp_enqueue_script('vilfIoApiShoptet', "https://vilfiodev.eu/api/wordpress-api.js#asyncload", array(), null, true);
        } else {
            wp_enqueue_script('vilfIoApi', "https://vilf.io/api/api.js#asyncload", array(), null, true);
            wp_enqueue_script('vilfIoApiShoptet', "https://vilf.io/api/wordpress-api.js#asyncload", array(), null, true);
        }
        ?>
        <noscript id="updated-cart-content" style="display: none"></noscript>
        <script type="application/javascript">
            var VilfIo = window.VilfIo || [];
            <?php
            VilfIoPublic::wooCommerceValues();
            ?>
            <?php
            VilfIoPublic::userName();
            ?>
            VilfIo.push(() => {
                <?php
                if(VILFIO_DEV){;?>
                VilfIo._useDev = true;
                <?php }?>
                VilfIo.SERVICE_WORKER_SCOPE = '/';
                VilfIo.SERVICE_WORKER_URL = '<?php echo VILFIO_PLUGIN_URL?>ServiceWorker.js.php';
                VilfIo.setUp(<?php echo $setting['app_id']?>);
            });
        </script>
        <?php
    }

    /**
     * Print user name if is logged in
     */
    public static function userName()
    {
        $setting = VilfIo::loadSetting();
        if (!$setting['tags_name']) {
            return;
        }

        if (is_user_logged_in()) {
            $current_user = wp_get_current_user();
            ?>
            let VILFIO_NAMES={
            user_login: '<?php echo $current_user->user_login; ?>',
            user_firstname: '<?php echo $current_user->user_firstname; ?>',
            user_lastname: '<?php echo $current_user->user_lastname; ?>',
            display_name: '<?php echo $current_user->display_name; ?>',
            };
            <?php
        }

    }

    /**
     * Get cart content as array
     * @return array
     */
    public static function getCartContent()
    {

        $products = WC()->cart->get_cart();

        $cart = [];
        $index = 0;
        foreach ($products as $values) {
            $_product = wc_get_product($values['data']->get_id());
            $cart[$index] = [];
            $cart[$index]['id'] = $values['data']->get_id();
            $cart[$index]['title'] = $_product->get_title();
            $cart[$index]['quantity'] = $values['quantity'];
            $price = get_post_meta($values['product_id'], '_price', true);
            $cart[$index]['price'] = $price;
            $index++;
        }
        return $cart;
    }

    /**
     * Print cart content to JS in order to set up abandoned cart notifications
     */
    public static function wooCommerceValues()
    {
        if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
            // WooCommerce is not active
            return;
        }

        $setting = VilfIo::loadSetting();
        if (!$setting['tags_cart']) {
            return;
        }

        echo 'let VILFIO_CURRENCY="' . html_entity_decode(get_woocommerce_currency_symbol(), ENT_COMPAT | ENT_HTML401, 'UTF-8') .'";';
        echo 'let VILFIO_CART=' . json_encode(self::getCartContent()) . ';';
    }

    /**
     * Add to thank page order info about order in order to send notification when order status changed and disable
     * sending abandoned cart notifications
     * @param $order_id
     */
    public static function jsTrackingThankYouPage($order_id)
    {
        if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
            // WooCommerce is not active
            return;
        }

        $setting = VilfIo::loadSetting();
        if (!$setting['tags_order']) {
            if ($setting['tags_cart']) {
                //order are disabled but cart is not. So we need need reset cart.
                ?>
                <script type="text/javascript">
                    const VILFIO_RESET_CART = '<?php echo $order_id; ?>';
                </script>
                <?php
                return;
            }
        }

        $order = wc_get_order($order_id);
        ?>
        <script type="text/javascript">
            const VILFIO_ORDER_ID = '<?php echo $order_id; ?>';
            const VILFIO_ORDER_LAST_NAME = '<?php echo $order->get_shipping_last_name(); ?>';
            const VILFIO_ORDER_FISRT_NAME = '<?php echo $order->get_shipping_first_name(); ?>';
            const VILFIO_STATUS = '<?php echo $order->get_status(); ?>';
            const VILFIO_ORDER_COMPLETE = [
                <?php
                foreach( $order->get_items() as $item ) :
                $product = $item->get_product();
                $item_data = $item->get_data();
                ?>
                {
                    name: '<?php echo $item_data['name']; ?>',
                    id: '<?php echo $item->get_product_id(); ?>',
                    price: '<?php echo $product->get_price(); ?>',
                    currency: '<?php echo $order->get_currency(); ?>',
                    orderQuantity: '<?php echo $item->get_quantity(); ?>',
                },
                <?php endforeach; // End of Loop ?>];
        </script>
        <?php
    }
}

?>

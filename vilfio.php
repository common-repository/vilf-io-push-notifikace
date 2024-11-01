<?php

defined('ABSPATH') or die('This page may not be accessed directly.');

/*
 * Plugin Name: Vilf.io Push notifikace
 * Plugin URI: https://vilf.io/
 * Description: Doplněk na rozesílání notifikací (opuštěný košík, akční nabídka ad.) na desktop i smartphony.Vilf.io funguje na WooCommerce i klasickém WordPressu.
 * Version: 1.1
 * Author: Vilf.io
 * Author URI: https://vilf.io
 * License: GPLv2 or later
 */

define('VILFIO_DEV', false);
define('VILFIO_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once plugin_dir_path(__FILE__).'vilfio-utils.php';
require_once plugin_dir_path(__FILE__).'vilfio-admin.php';
require_once plugin_dir_path(__FILE__).'vilfio-public.php';
require_once plugin_dir_path(__FILE__).'vilfio-settings.php';

add_action('init', array('VilfIoAdmin', 'init'));
add_action('init', array('VilfIoPublic', 'init'));

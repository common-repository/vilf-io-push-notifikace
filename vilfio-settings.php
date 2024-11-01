<?php

defined('ABSPATH') or die('This page may not be accessed directly.');

class VilfIo
{
    /**
     * Load plugin settings.
     *
     * @return array|mixed|void
     */
    public static function loadSetting()
    {
        $defaults = array(
            'app_id' => '',
            'api_token' => '',
            'icon_url' => '',
            'tags_name' => true,
            'tags_cart' => true,
            'tags_order' => true,
        );

        $settings = get_option('VILF-IO-SETTING');
        if (empty($settings)) {
            $settings = array();
        }

        reset($defaults);
        foreach ($defaults as $key => $value) {
            if (!array_key_exists($key, $settings)) {
                $settings[$key] = $value;
            }
        }

        return $settings;
    }

    /**
     * Save plugin setting.
     *
     * @param $settings
     */
    public static function saveVilfIoSettings($settings)
    {
        $settingsToSave = $settings;
        update_option("VILF-IO-SETTING", $settingsToSave);
    }
}
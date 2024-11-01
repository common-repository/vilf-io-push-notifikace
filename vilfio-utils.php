<?php

defined('ABSPATH') or die('This page may not be accessed directly.');

class VilfIoUtils
{
    /**
     * Can user modify plugin setting?
     *
     * @return bool
     */
    public static function can_modify_plugin_settings()
    {
        return current_user_can('delete_users');
    }
    /**
     * Can user modify plugin setting?
     *
     * @return bool
     */
    public static function can_send_notifications()
    {
        return current_user_can('publish_posts') || current_user_can('edit_published_posts');
    }
}
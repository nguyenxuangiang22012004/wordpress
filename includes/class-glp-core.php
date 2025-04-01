<?php
if (!defined('ABSPATH')) {
    exit;
}

class GLP_Core
{
    public static function init()
    {
        // Thêm menu admin
        add_action('admin_menu', array(__CLASS__, 'register_admin_menu'));
    }

    public static function register_admin_menu()
    {
        add_menu_page(
            'Gym Landing',
            'Gym Landing',
            'manage_options',
            'gym-landing-settings',
            array(__CLASS__, 'admin_page'),
            'dashicons-heart'
        );
    }

    public static function admin_page()
    {
        require_once GLP_PLUGIN_DIR . 'includes/admin/admin.php';
    }
}

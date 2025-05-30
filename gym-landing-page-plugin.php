<?php
/*
 * Plugin Name: Gym Landing Plugin
 * Description: Plugin hỗ trợ landing page gym với các module riêng biệt.
 * Version: 1.0
 * Author: Your Name
 */

if (!defined('ABSPATH')) {
    exit;
}

define('GLP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('GLP_PLUGIN_URL', plugin_dir_url(__FILE__));

// Load core class
require_once GLP_PLUGIN_DIR . 'includes/class-glp-core.php';
require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';

// Khởi tạo plugin
class GymLandingPlugin
{
    private static $instance;

    public static function get_instance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct()
    {
        // Load core
        GLP_Core::init();

        // Load modules
        $this->load_modules();

        // Hook assets
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
    }

    private function load_modules()
    {
        $modules = array(
            'lead-management' => GLP_PLUGIN_DIR . 'includes/modules/lead-management/lead-management.php',
            'popup-offers' => GLP_PLUGIN_DIR . 'includes/modules/popup-offers/popup-offers.php',
            'class-schedule' => GLP_PLUGIN_DIR . 'includes/modules/class-schedule/class-schedule.php',
            'testimonials' => GLP_PLUGIN_DIR . 'includes/modules/testimonials/testimonials.php',
            'product-management' => GLP_PLUGIN_DIR . 'includes/modules/product-management/product-management.php',
            'analytics-tracking' => GLP_PLUGIN_DIR . 'includes/modules/analytics-tracking/analytics-tracking.php',
        );

        foreach ($modules as $module => $path) {
            if (file_exists($path)) {
                require_once $path;
            }
        }
    }

    public function enqueue_assets()
    {
        wp_enqueue_script('jquery');
        // Bootstrap CSS và JS
        wp_enqueue_style('bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css', array(), '5.0.2');
        wp_enqueue_script('bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js', array('jquery'), '5.0.2', true);

        // CSS và JS của plugin
        wp_enqueue_style('glp-styles', GLP_PLUGIN_URL . 'assets/css/style.css', array('bootstrap'), '1.0.2'); // Thêm dependency
        wp_enqueue_script('glp-popup', GLP_PLUGIN_URL . 'assets/js/popup.js', array('jquery'), '1.0.1', true);
        wp_enqueue_style('glcp-testimonials', GLP_PLUGIN_URL . 'assets/css/testimonials.css', array('bootstrap'), '1.0.1');

        // Font Awesome
        wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css', array(), '6.7.2');

        // Slick Slider
        wp_enqueue_style('slick-css', 'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.css', array(), '1.8.1');
        wp_enqueue_script('slick-js', 'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.min.js', array('jquery'), '1.8.1', true);
        wp_enqueue_script('glcp-testimonials-slider', GLP_PLUGIN_URL . 'assets/js/testimonials-slider.js', array('jquery', 'slick-js'), '1.0.1', true);
    }
    public function enqueue_admin_assets()
    {
        wp_enqueue_style('slick-css', 'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.css', array(), '1.8.1');
        wp_enqueue_script('slick-js', 'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.min.js', array('jquery'), '1.8.1', true);
        // Thêm Bootstrap CSS

        wp_enqueue_style('bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css');
        // Thêm Bootstrap JS (cần jQuery)
        wp_enqueue_script('bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js', array('jquery'), '5.0.2', true);
        // Thêm custom CSS cho admin (nếu cần)
        wp_enqueue_style('glp-admin-styles', GLP_PLUGIN_URL . 'assets/css/admin-style.css');
        // Thêm Tailwind CSS và Font Awesome cho frontend
        wp_enqueue_style('glcp-testimonials', GLP_PLUGIN_URL . 'assets/css/testimonials.css', array(), '1.0.1'); // Tăng version // Thêm CSS cho testimonials
        wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css', array(), '6.7.2');
    }
}

// Khởi chạy plugin
GymLandingPlugin::get_instance();

// Tạo bảng orders khi kích hoạt plugin
function glp_create_orders_table()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'glp_orders';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        order_id BIGINT(20) UNSIGNED NOT NULL,
        products LONGTEXT NOT NULL,
        customer_name VARCHAR(255) NOT NULL,
        customer_phone VARCHAR(20) NOT NULL,
        customer_address TEXT NOT NULL,
        date DATETIME NOT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'new',
        payment_method VARCHAR(20) NOT NULL,
        payment_status VARCHAR(20) NOT NULL DEFAULT 'pending',
        transaction_id VARCHAR(50) DEFAULT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY order_id (order_id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
register_activation_hook(__FILE__, 'glp_create_orders_table');

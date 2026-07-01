<?php
/**
 * Plugin Name: WooCommerce Notify Me When Available
 * Description: Легковесный плагин для подписки на уведомления о появлении товара.
 * Version: 1.0.0
 * Requires at least: 6.0
 * Requires PHP: 8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Проверка на установленный WooCommerce
add_action( 'plugins_loaded', 'wc_notify_check_dependencies' );
function wc_notify_check_dependencies() {
    if ( ! class_exists( 'WooCommerce' ) ) {
        add_action( 'admin_notices', function() {
            echo '<div class="error"><p>Плагин "WooCommerce Notify Me" требует WooCommerce для работы!</p></div>';
        } );
        return;
    }
}

define( 'WC_NOTIFY_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WC_NOTIFY_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Активация плагина: создание кастомной таблицы
register_activation_hook( __FILE__, 'wc_notify_install_db' );

function wc_notify_install_db() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'wc_stock_notifications';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        product_id bigint(20) NOT NULL,
        variation_id bigint(20) DEFAULT 0 NOT NULL,
        user_id bigint(20) DEFAULT 0 NOT NULL,
        email varchar(100) NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        status varchar(20) DEFAULT 'pending' NOT NULL,
        PRIMARY KEY  (id),
        KEY product_id (product_id),
        KEY email (email)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
}

// Подключаем основные классы
require_once WC_NOTIFY_PLUGIN_DIR . 'includes/class-rest-api.php';
require_once WC_NOTIFY_PLUGIN_DIR . 'includes/class-frontend.php';
require_once WC_NOTIFY_PLUGIN_DIR . 'includes/class-stock-trigger.php';
require_once WC_NOTIFY_PLUGIN_DIR . 'includes/class-mailer.php';
require_once WC_NOTIFY_PLUGIN_DIR . 'includes/class-admin.php';
require_once WC_NOTIFY_PLUGIN_DIR . 'includes/class-my-account.php';
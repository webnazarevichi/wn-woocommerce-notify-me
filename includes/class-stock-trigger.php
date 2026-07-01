<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WC_Notify_Stock_Trigger {

    public static function init() {
        // Хук срабатывает при обновлении статуса запасов товара
        add_action( 'woocommerce_product_set_stock_status', [ __CLASS__, 'schedule_notifications' ], 10, 3 );
        add_action( 'woocommerce_variation_set_stock_status', [ __CLASS__, 'schedule_notifications' ], 10, 3 );
    }

    public static function schedule_notifications( $product_id, $stock_status, $product ) {
        if ( $stock_status !== 'instock' ) return;
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'wc_stock_notifications';
        
        // Проверяем, это вариация или основной товар?
        $variation_id = 0;
        if ( $product instanceof WC_Product_Variation ) {
            $variation_id = $product->get_id();
            $product_id = $product->get_parent_id();
        }
        
        // Ищем подписчиков с учетом variation_id
        $query = $wpdb->prepare(
            "SELECT COUNT(id) FROM $table_name 
            WHERE product_id = %d 
            AND (variation_id = %d OR variation_id = 0) 
            AND status = 'pending'",
            $product_id, $variation_id
        );
        
        $has_subscribers = $wpdb->get_var( $query );
        
        if ( $has_subscribers > 0 && function_exists( 'as_enqueue_async_action' ) ) {
            as_enqueue_async_action( 'wc_notify_send_emails', [ 
                'product_id' => $product_id,
                'variation_id' => $variation_id 
            ] );
        }
    }
}
WC_Notify_Stock_Trigger::init();
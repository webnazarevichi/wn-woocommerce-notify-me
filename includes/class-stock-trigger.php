<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WC_Notify_Stock_Trigger {

    public static function init() {
        // Хук срабатывает при обновлении статуса запасов товара
        add_action( 'woocommerce_product_set_stock_status', [ __CLASS__, 'schedule_notifications' ], 10, 3 );
        add_action( 'woocommerce_variation_set_stock_status', [ __CLASS__, 'schedule_notifications' ], 10, 3 );
    }

    public static function schedule_notifications( $product_id, $stock_status, $product ) {
        // Реагируем только если товар появился в наличии
        if ( $stock_status !== 'instock' ) {
            return;
        }

        // Проверяем, есть ли активные подписки на этот товар
        global $wpdb;
        $table_name = $wpdb->prefix . 'wc_stock_notifications';
        
        $has_subscribers = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(id) FROM $table_name WHERE product_id = %d AND status = 'pending'",
            $product_id
        ) );

        if ( $has_subscribers > 0 ) {
            // Если Action Scheduler доступен (встроен в WC), ставим задачу в очередь
            if ( function_exists( 'as_enqueue_async_action' ) ) {
                // Создаем кастомный экшен 'wc_notify_send_emails', который будет пакетно рассылать письма
                as_enqueue_async_action( 'wc_notify_send_emails', [ 'product_id' => $product_id ] );
            }
        }
    }
}
WC_Notify_Stock_Trigger::init();
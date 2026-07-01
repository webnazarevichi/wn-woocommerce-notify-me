<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WC_Notify_Mailer {

    public static function init() {
        // Хук, который мы вызывали через as_enqueue_async_action
        add_action( 'wc_notify_send_emails', [ __CLASS__, 'process_batch' ] );
    }

    public static function process_batch( $product_id, $variation_id = 0 ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wc_stock_notifications';
        $batch_size = 50; // Отправляем по 50 писем за один проход

        // Получаем партию подписчиков
        if ( $variation_id ) {
            $subscribers = $wpdb->get_results( $wpdb->prepare(
                "SELECT id, email, variation_id FROM $table_name WHERE product_id = %d AND variation_id = %d AND status = 'pending' LIMIT %d",
                $product_id, $variation_id, $batch_size
            ) );
        } else {
            $subscribers = $wpdb->get_results( $wpdb->prepare(
                "SELECT id, email, variation_id FROM $table_name WHERE product_id = %d AND status = 'pending' LIMIT %d",
                $product_id, $batch_size
            ) );
        }

        if ( empty( $subscribers ) ) return;

        $product = wc_get_product( $product_id );
        if ( ! $product ) return;

        $mailer = WC()->mailer(); // Используем нативный мейлер WooCommerce для красивых шаблонов
        
        foreach ( $subscribers as $sub ) {
            $target_product = $sub->variation_id ? wc_get_product( $sub->variation_id ) : $product;
            if ( ! $target_product ) continue;

            // Trigger the custom WooCommerce email
            if ( function_exists( 'WC' ) ) {
                $mailer = WC()->mailer();
                $emails = $mailer->get_emails();
                if ( isset( $emails['WC_Notify_Email_Instock'] ) ) {
                    $emails['WC_Notify_Email_Instock']->trigger( $sub->id );
                }
            }

            // Обновляем статус 
            $wpdb->update(
                $table_name,
                [ 'status' => 'sent', 'sent_at' => current_time( 'mysql' ) ],
                [ 'id' => $sub->id ],
                [ '%s', '%s' ],
                [ '%d' ]
            );
        }

        // Проверяем, остались ли еще подписчики для этого товара
        $pending_count = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(id) FROM $table_name WHERE product_id = %d AND status = 'pending'",
            $product_id
        ) );

        // Если остались, планируем следующий батч
        if ( $pending_count > 0 && function_exists( 'as_enqueue_async_action' ) ) {
            as_enqueue_async_action( 'wc_notify_send_emails', [ 'product_id' => $product_id ] );
        }
    }
}
WC_Notify_Mailer::init();
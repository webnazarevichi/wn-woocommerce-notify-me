<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WC_Notify_Mailer {

    public static function init() {
        // Хук, который мы вызывали через as_enqueue_async_action
        add_action( 'wc_notify_send_emails', [ __CLASS__, 'process_batch' ] );
    }

    public static function process_batch( $product_id ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wc_stock_notifications';
        $batch_size = 50; // Отправляем по 50 писем за один проход

        // Получаем партию подписчиков
        $subscribers = $wpdb->get_results( $wpdb->prepare(
            "SELECT id, email, variation_id FROM $table_name WHERE product_id = %d AND status = 'pending' LIMIT %d",
            $product_id, $batch_size
        ) );

        if ( empty( $subscribers ) ) return;

        $product = wc_get_product( $product_id );
        if ( ! $product ) return;

        $mailer = WC()->mailer(); // Используем нативный мейлер WooCommerce для красивых шаблонов
        
        foreach ( $subscribers as $sub ) {
            $target_product = $sub->variation_id ? wc_get_product( $sub->variation_id ) : $product;
            if ( ! $target_product ) continue;

            $subject = sprintf( 'Товар "%s" снова в наличии!', $target_product->get_name() );
            
            // Формируем аккуратное тело письма
            $message  = '<p>Здравствуйте!</p>';
            $message .= sprintf( '<p>Товар <strong>%s</strong>, которым вы интересовались, снова доступен для заказа.</p>', $target_product->get_name() );
            $message .= sprintf( '<p><a href="%s" style="display:inline-block; padding:10px 20px; background:#000; color:#fff; text-decoration:none;">Перейти к товару</a></p>', $target_product->get_permalink() );

            // Оборачиваем в стандартный шаблон WC
            $email_content = $mailer->wrap_message( $subject, $message );
            
            // Отправляем письмо
            wc_mail( $sub->email, $subject, $email_content );

            // Обновляем статус на sent
            $wpdb->update( 
                $table_name, 
                [ 'status' => 'sent' ], 
                [ 'id' => $sub->id ], 
                [ '%s' ], 
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
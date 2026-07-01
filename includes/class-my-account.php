<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WC_Notify_My_Account {

    public static function init() {
        add_action( 'init', [ __CLASS__, 'add_endpoint' ] );
        add_filter( 'woocommerce_account_menu_items', [ __CLASS__, 'add_menu_item' ] );
        add_action( 'woocommerce_account_waitlist_endpoint', [ __CLASS__, 'endpoint_content' ] );
        
        // Обработка удаления (отписки) через POST запрос для простоты
        add_action( 'template_redirect', [ __CLASS__, 'handle_unsubscribe' ] );
    }

    public static function add_endpoint() {
        add_rewrite_endpoint( 'waitlist', EP_ROOT | EP_PAGES );
    }

    public static function add_menu_item( $items ) {
        // Вставляем новую вкладку перед "Выйти"
        $logout = $items['customer-logout'];
        unset( $items['customer-logout'] );
        $items['waitlist'] = 'Лист ожидания';
        $items['customer-logout'] = $logout;
        
        return $items;
    }

    public static function endpoint_content() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wc_stock_notifications';
        $user_id = get_current_user_id();

        $subscriptions = $wpdb->get_results( $wpdb->prepare(
            "SELECT id, product_id, variation_id FROM $table_name WHERE user_id = %d AND status = 'pending'",
            $user_id
        ) );

        echo '<h3>Товары, которые вы ожидаете</h3>';

        if ( empty( $subscriptions ) ) {
            echo '<p>Ваш лист ожидания пуст.</p>';
            return;
        }

        echo '<table class="woocommerce-orders-table woocommerce-MyAccount-orders shop_table shop_table_responsive my_account_orders account-orders-table">';
        echo '<thead><tr><th>Товар</th><th>Действие</th></tr></thead><tbody>';
        
        foreach ( $subscriptions as $sub ) {
            $product = wc_get_product( $sub->variation_id ? $sub->variation_id : $sub->product_id );
            if ( ! $product ) continue;

            $unsubscribe_url = wp_nonce_url( add_query_arg( [
                'action' => 'wc_notify_unsubscribe',
                'sub_id' => $sub->id
            ], wc_get_endpoint_url( 'waitlist', '', wc_get_page_permalink( 'myaccount' ) ) ), 'wc_notify_del' );

            echo '<tr>';
            echo '<td><a href="' . esc_url( $product->get_permalink() ) . '">' . esc_html( $product->get_name() ) . '</a></td>';
            echo '<td><a href="' . esc_url( $unsubscribe_url ) . '" class="button" style="padding: 5px 10px; background: #e2401c; color: #fff;">Отписаться</a></td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
    }

    public static function handle_unsubscribe() {
        if ( isset( $_GET['action'] ) && $_GET['action'] === 'wc_notify_unsubscribe' && isset( $_GET['sub_id'] ) ) {
            if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'wc_notify_del' ) ) {
                return; // Защита от случайного удаления
            }

            global $wpdb;
            $wpdb->delete( 
                $wpdb->prefix . 'wc_stock_notifications', 
                [ 
                    'id'      => absint( $_GET['sub_id'] ),
                    'user_id' => get_current_user_id() // Убеждаемся, что удаляет владелец
                ], 
                [ '%d', '%d' ] 
            );

            wp_safe_redirect( wc_get_endpoint_url( 'waitlist', '', wc_get_page_permalink( 'myaccount' ) ) );
            exit;
        }
    }
}
WC_Notify_My_Account::init();
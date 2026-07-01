<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WC_Notify_REST_API {

    public static function init() {
        add_action( 'rest_api_init', [ __CLASS__, 'register_routes' ] );
    }

    public static function register_routes() {
        register_rest_route( 'wc-notify/v1', '/subscribe', [
            'methods'             => 'POST',
            'callback'            => [ __CLASS__, 'handle_subscription' ],
            'permission_callback' => '__return_true', // Публичный эндпоинт
        ] );
    }

    public static function handle_subscription( WP_REST_Request $request ) {
        global $wpdb;

        // 1. Проверка Nonce
        $nonce = $request->get_header( 'X-WP-Nonce' );
        if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
            return new WP_Error( 'rest_forbidden', 'Invalid Nonce.', [ 'status' => 403 ] );
        }

        // 2. Honeypot (Защита от глупых ботов)
        if ( ! empty( $request->get_param( 'website_url_hp' ) ) ) {
            return new WP_Error( 'rest_forbidden', 'Spam detected.', [ 'status' => 400 ] );
        }

        // 3. Rate Limiting (Защита от перебора по IP)
        $ip = sanitize_text_field( $_SERVER['REMOTE_ADDR'] );
        $transient_key = 'wc_notify_limit_' . md5( $ip );
        $requests = get_transient( $transient_key ) ?: 0;
        
        if ( $requests > 3 ) {
            return new WP_Error( 'too_many_requests', 'Слишком много попыток. Подождите пару минут.', [ 'status' => 429 ] );
        }
        set_transient( $transient_key, $requests + 1, 5 * MINUTE_IN_SECONDS );

        // 4. Валидация данных
        $email = sanitize_email( $request->get_param( 'email' ) );
        $product_id = absint( $request->get_param( 'product_id' ) );
        $variation_id = absint( $request->get_param( 'variation_id' ) );

        if ( ! is_email( $email ) || ! $product_id ) {
            return new WP_Error( 'invalid_data', 'Некорректный Email или товар.', [ 'status' => 400 ] );
        }

        $table_name = $wpdb->prefix . 'wc_stock_notifications';
        $user_id = get_current_user_id();

        // 5. Проверка на дубликат (чтобы не спамить в БД)
        $exists = $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM $table_name WHERE email = %s AND product_id = %d AND variation_id = %d AND status = 'pending'",
            $email, $product_id, $variation_id
        ) );

        if ( $exists ) {
            return rest_ensure_response( [ 'success' => false, 'message' => 'Вы уже подписаны на уведомления для этого товара.' ] );
        }

        // 6. Запись в БД
        $wpdb->insert( $table_name, [
            'product_id'   => $product_id,
            'variation_id' => $variation_id,
            'user_id'      => $user_id,
            'email'        => $email,
            'status'       => 'pending'
        ] );

        return rest_ensure_response( [ 'success' => true, 'message' => 'Спасибо! Мы уведомим вас, когда товар появится в наличии.' ] );
    }
}
WC_Notify_REST_API::init();
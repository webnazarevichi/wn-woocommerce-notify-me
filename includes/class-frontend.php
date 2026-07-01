<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WC_Notify_Frontend {

    public static function init() {
        // Кнопка на странице товара
        add_action( 'woocommerce_single_product_summary', [ __CLASS__, 'add_notify_button' ], 35 );
        // Для вариативных товаров можно выводить и через хук вариаций, но мы скрываем/показываем её через JS.
        
        // Кнопка в каталоге
        $catalog_display = get_option( 'wc_notify_catalog_display', 'none' );
        if ( $catalog_display === 'replace' ) {
            add_filter( 'woocommerce_loop_add_to_cart_link', [ __CLASS__, 'catalog_replace_button' ], 10, 3 );
        } elseif ( $catalog_display === 'after' ) {
            add_action( 'woocommerce_after_shop_loop_item', [ __CLASS__, 'catalog_after_button' ], 11 );
        }

        add_action( 'wp_footer', [ __CLASS__, 'render_popup_html' ] );
        add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue_scripts' ] );
    }

    public static function add_notify_button() {
        global $product;
        if ( ! $product ) return;

        $btn_text = get_option( 'wc_notify_btn_text', 'Уведомить о наличии' );
        
        if ( $product->is_type( 'variable' ) ) {
            // Для вариативных товаров выводим скрытую кнопку, скрипт покажет её когда выбрана вариация не в наличии
            echo '<button type="button" id="wc-notify-btn" class="btn button product-notify-btn open-notify-form" data-product-id="' . esc_attr( $product->get_id() ) . '" style="display:none; margin-top:10px;">' . esc_html( $btn_text ) . '</button>';
        } elseif ( ! $product->is_in_stock() ) {
            // Для простых товаров не в наличии
            echo '<button type="button" id="wc-notify-btn" class="btn button product-notify-btn open-notify-form" data-product-id="' . esc_attr( $product->get_id() ) . '">' . esc_html( $btn_text ) . '</button>';
        }
    }

    public static function catalog_replace_button( $link, $product, $args ) {
        if ( ! $product->is_in_stock() ) {
            $btn_text = get_option( 'wc_notify_btn_text', 'Уведомить о наличии' );
            return sprintf(
                '<a href="#" class="button open-notify-form" data-product-id="%s">%s</a>',
                esc_attr( $product->get_id() ),
                esc_html( $btn_text )
            );
        }
        return $link;
    }

    public static function catalog_after_button() {
        global $product;
        if ( ! $product || $product->is_in_stock() ) return;
        
        $btn_text = get_option( 'wc_notify_btn_text', 'Уведомить о наличии' );
        echo sprintf(
            '<a href="#" class="button open-notify-form" data-product-id="%s" style="margin-top:5px; display:block; text-align:center;">%s</a>',
            esc_attr( $product->get_id() ),
            esc_html( $btn_text )
        );
    }

    public static function render_popup_html() {
        $current_email = is_user_logged_in() ? wp_get_current_user()->user_email : '';
        ?>
        <div id="wc-notify-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center;">
            <div style="background:#fff; padding:30px; max-width:400px; width:100%; border-radius:4px; position:relative;">
                <span id="wc-notify-close" style="position:absolute; top:10px; right:15px; cursor:pointer; font-size:20px;">&times;</span>
                <h3 style="margin-top:0;">Уведомить о наличии</h3>
                <form id="wc-notify-form">
                    <input type="text" name="website_url_hp" style="display:none !important;" tabindex="-1" autocomplete="off">
                    
                    <p>
                        <input type="email" id="wc-notify-email" value="<?php echo esc_attr($current_email); ?>" placeholder="Ваш Email" required style="width:100%; padding:10px; margin-bottom:15px;">
                    </p>
                    <input type="hidden" id="wc-notify-product-id" value="">
                    <input type="hidden" id="wc-notify-variation-id" value="0">
                    <button type="submit" class="button alt" style="width:100%;">Отправить</button>
                    <div id="wc-notify-message" style="margin-top:15px; font-size:14px; text-align:center;"></div>
                </form>
            </div>
        </div>
        <?php
    }

    public static function enqueue_scripts() {
        wp_enqueue_script( 'wc-notify-js', WC_NOTIFY_PLUGIN_URL . 'assets/wc-notify.js', ['jquery'], '1.0.0', true );
        wp_localize_script( 'wc-notify-js', 'wcNotifyParams', [
            'restUrl' => esc_url_raw( rest_url( 'wc-notify/v1/subscribe' ) ),
            'nonce'   => wp_create_nonce( 'wp_rest' )
        ] );
    }
}
WC_Notify_Frontend::init();
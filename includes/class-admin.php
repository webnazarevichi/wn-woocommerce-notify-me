<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WC_Notify_Admin {

    public static function init() {
        add_action( 'admin_menu', [ __CLASS__, 'add_admin_menu' ] );
        add_action( 'admin_init', [ __CLASS__, 'register_settings' ] );
    }

    public static function add_admin_menu() {
        add_submenu_page(
            'woocommerce',
            'Уведомления о наличии',
            'Лист ожидания',
            'manage_woocommerce',
            'wc-notify-settings',
            [ __CLASS__, 'render_admin_page' ]
        );
    }

    public static function register_settings() {
        register_setting( 'wc_notify_options_group', 'wc_notify_btn_text' );
        register_setting( 'wc_notify_options_group', 'wc_notify_success_msg' );
        register_setting( 'wc_notify_options_group', 'wc_notify_catalog_display' );
    }

    public static function render_admin_page() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) return;
        
        $active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'dashboard';
        ?>
        <div class="wrap">
            <h1>Уведомления о наличии (Лист ожидания)</h1>
            <h2 class="nav-tab-wrapper">
                <a href="?page=wc-notify-settings&tab=dashboard" class="nav-tab <?php echo $active_tab == 'dashboard' ? 'nav-tab-active' : ''; ?>">Аналитика и Подписчики</a>
                <a href="?page=wc-notify-settings&tab=settings" class="nav-tab <?php echo $active_tab == 'settings' ? 'nav-tab-active' : ''; ?>">Настройки</a>
            </h2>

            <div style="background: #fff; padding: 20px; margin-top: 20px; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
                <?php if ( $active_tab === 'settings' ) : ?>
                    
                    <form method="post" action="options.php">
                        <?php settings_fields( 'wc_notify_options_group' ); ?>
                        <table class="form-table">
                            <tr valign="top">
                                <th scope="row">Текст кнопки на странице товара</th>
                                <td>
                                    <input type="text" name="wc_notify_btn_text" value="<?php echo esc_attr( get_option( 'wc_notify_btn_text', 'Уведомить о наличии' ) ); ?>" class="regular-text" />
                                </td>
                            </tr>
                            <tr valign="top">
                                <th scope="row">Отображение в каталоге</th>
                                <td>
                                    <select name="wc_notify_catalog_display">
                                        <option value="none" <?php selected( get_option( 'wc_notify_catalog_display', 'none' ), 'none' ); ?>>Не выводить кнопку (по умолчанию)</option>
                                        <option value="replace" <?php selected( get_option( 'wc_notify_catalog_display', 'none' ), 'replace' ); ?>>Заменять кнопку "В корзину"</option>
                                        <option value="after" <?php selected( get_option( 'wc_notify_catalog_display', 'none' ), 'after' ); ?>>Выводить под кнопкой "В корзину"</option>
                                    </select>
                                    <p class="description">Выберите, как отображать кнопку подписки в списках товаров.</p>
                                </td>
                            </tr>
                            <tr valign="top">
                                <th scope="row">Сообщение об успешной подписке</th>
                                <td>
                                    <input type="text" name="wc_notify_success_msg" value="<?php echo esc_attr( get_option( 'wc_notify_success_msg', 'Спасибо! Мы уведомим вас, когда товар появится в наличии.' ) ); ?>" class="regular-text" />
                                </td>
                            </tr>
                        </table>
                        <?php submit_button(); ?>
                    </form>

                <?php else : ?>
                    
                    <form method="post">
                    <?php
                        require_once dirname( __FILE__ ) . '/class-subscribers-list-table.php';
                        $list_table = new WC_Notify_Subscribers_List_Table();
                        $list_table->prepare_items();
                        $list_table->display();
                    ?>
                    </form>
                    
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
}
WC_Notify_Admin::init();
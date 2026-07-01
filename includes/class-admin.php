<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WC_Notify_Admin {

    public static function init() {
        add_action( 'admin_menu', [ __CLASS__, 'add_admin_menu' ] );
        add_action( 'admin_init', [ __CLASS__, 'register_settings' ] );
        add_action( 'admin_init', [ __CLASS__, 'process_actions' ] );
    }

    public static function process_actions() {
        if ( isset( $_GET['page'] ) && $_GET['page'] === 'wc-notify-settings' ) {
            if ( isset( $_GET['action'] ) && isset( $_GET['subscriber'] ) ) {
                $sub_id = absint( $_GET['subscriber'] );
                $action = sanitize_text_field( $_GET['action'] );
                
                if ( $action === 'send_mail' && check_admin_referer( 'send_mail_' . $sub_id ) ) {
                    if ( function_exists( 'WC' ) ) {
                        $mailer = WC()->mailer();
                        $emails = $mailer->get_emails();
                        if ( isset( $emails['WC_Notify_Email_Instock'] ) ) {
                            $emails['WC_Notify_Email_Instock']->trigger( $sub_id );
                        }
                    }
                    global $wpdb;
                    $table = $wpdb->prefix . 'wc_stock_notifications';
                    $wpdb->update( $table, [ 'status' => 'sent', 'sent_at' => current_time( 'mysql' ) ], [ 'id' => $sub_id ] );
                    wp_redirect( admin_url( 'admin.php?page=wc-notify-settings&tab=subscribers&message=mail_sent' ) );
                    exit;
                }
                
                if ( $action === 'delete_sub' && check_admin_referer( 'delete_sub_' . $sub_id ) ) {
                    global $wpdb;
                    $table = $wpdb->prefix . 'wc_stock_notifications';
                    $wpdb->delete( $table, [ 'id' => $sub_id ] );
                    wp_redirect( admin_url( 'admin.php?page=wc-notify-settings&tab=subscribers&message=deleted' ) );
                    exit;
                }
            }
        }
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
        register_setting( 'wc_notify_options_group', 'wc_notify_delete_days' );
    }

    public static function render_admin_page() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) return;
        
        $active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'subscribers';
        ?>
        <div class="wrap">
            <h1>Уведомления о наличии (Лист ожидания)</h1>
            <h2 class="nav-tab-wrapper">
                <a href="?page=wc-notify-settings&tab=subscribers" class="nav-tab <?php echo $active_tab == 'subscribers' ? 'nav-tab-active' : ''; ?>">Подписчики</a>
                <a href="?page=wc-notify-settings&tab=dashboard" class="nav-tab <?php echo $active_tab == 'dashboard' ? 'nav-tab-active' : ''; ?>">Аналитика</a>
                <a href="?page=wc-notify-settings&tab=settings" class="nav-tab <?php echo $active_tab == 'settings' ? 'nav-tab-active' : ''; ?>">Настройки</a>
            </h2>

            <div style="background: #fff; padding: 20px; margin-top: 20px; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
                <?php if ( isset( $_GET['message'] ) ) : ?>
                    <div class="updated notice is-dismissible">
                        <p><?php 
                            if ( $_GET['message'] === 'mail_sent' ) echo 'Письмо успешно отправлено!';
                            if ( $_GET['message'] === 'deleted' ) echo 'Подписка успешно удалена!';
                        ?></p>
                    </div>
                <?php endif; ?>

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
                            <tr valign="top">
                                <th scope="row">Автоматическое удаление подписчиков</th>
                                <td>
                                    <input type="number" name="wc_notify_delete_days" value="<?php echo esc_attr( get_option( 'wc_notify_delete_days', '0' ) ); ?>" class="small-text" min="0" step="1" /> дней
                                    <p class="description">Укажите количество дней после отправки письма о наличии, через которое подписка будет автоматически удалена. Установите 0, чтобы не удалять.</p>
                                </td>
                            </tr>
                        </table>
                        <?php submit_button(); ?>
                    </form>

                <?php elseif ( $active_tab == 'dashboard' ) : ?>
                    
                    <h3>Аналитика по товарам (ожидают появления)</h3>
                    <?php
                    global $wpdb;
                    $table_name = $wpdb->prefix . 'wc_stock_notifications';
                    
                    // Группируем по товарам и считаем количество подписок (pending)
                    $analytics = $wpdb->get_results( "
                        SELECT product_id, COUNT(id) as count 
                        FROM $table_name 
                        WHERE status = 'pending' 
                        GROUP BY product_id 
                        ORDER BY count DESC 
                        LIMIT 50
                    " );

                    if ( ! empty( $analytics ) ) {
                        echo '<table class="wp-list-table widefat fixed striped">';
                        echo '<thead><tr><th>Название товара</th><th>Активные подписки</th><th>Действия</th></tr></thead><tbody>';
                        foreach ( $analytics as $row ) {
                            $product = wc_get_product( $row->product_id );
                            if ( ! $product ) continue;
                            
                            $edit_link = admin_url( 'post.php?post=' . $row->product_id . '&action=edit' );
                            echo '<tr>';
                            echo '<td><strong><a href="' . esc_url( $edit_link ) . '">' . esc_html( $product->get_name() ) . '</a></strong></td>';
                            echo '<td>' . absint( $row->count ) . '</td>';
                            echo '<td><a href="' . esc_url( $edit_link ) . '">Редактировать товар</a></td>';
                            echo '</tr>';
                        }
                        echo '</tbody></table>';
                    } else {
                        echo '<p>На данный момент активных подписок нет.</p>';
                    }
                    ?>
                
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
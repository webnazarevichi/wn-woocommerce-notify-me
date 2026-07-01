<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class WC_Notify_Subscribers_List_Table extends WP_List_Table {

    public function __construct() {
        parent::__construct( [
            'singular' => 'subscriber',
            'plural'   => 'subscribers',
            'ajax'     => false
        ] );
    }

    public function get_columns() {
        return [
            'cb'          => '<input type="checkbox" />',
            'email'       => 'Email',
            'status'      => 'Статус',
            'product'     => 'Товар',
            'registered'  => 'Зарегистрирован',
            'created_at'  => 'Дата подписки'
        ];
    }

    public function get_sortable_columns() {
        return [
            'email'      => [ 'email', false ],
            'status'     => [ 'status', false ],
            'created_at' => [ 'created_at', true ]
        ];
    }

    public function column_email( $item ) {
        $actions = [
            'send_mail' => sprintf( '<a href="?page=%s&tab=subscribers&action=%s&subscriber=%s&_wpnonce=%s">Отправить email о наличии</a>', $_REQUEST['page'], 'send_mail', $item['id'], wp_create_nonce('send_mail_'.$item['id']) ),
            'delete'    => sprintf( '<a href="?page=%s&tab=subscribers&action=%s&subscriber=%s&_wpnonce=%s" style="color:red;">Удалить</a>', $_REQUEST['page'], 'delete_sub', $item['id'], wp_create_nonce('delete_sub_'.$item['id']) ),
        ];
        
        return sprintf('%1$s %2$s', esc_html( $item['email'] ), $this->row_actions($actions) );
    }

    public function column_default( $item, $column_name ) {
        switch ( $column_name ) {
            case 'status':
                if ( $item['status'] === 'sent' ) {
                    return '<span style="background:#c6e1c6; color:#5b841b; padding:4px 8px; border-radius:4px; font-weight:bold; font-size:11px;">MAIL SENT</span>';
                } else {
                    return '<span style="background:#007cba; color:#fff; padding:4px 8px; border-radius:4px; font-weight:bold; font-size:11px;">SUBSCRIBED</span>';
                }
            case 'product':
                $product = wc_get_product( $item['variation_id'] ? $item['variation_id'] : $item['product_id'] );
                if ( $product ) {
                    $stock_status = $product->is_in_stock() ? '<span style="color:green;">В наличии</span>' : '<span style="color:#d63638;">Нет в наличии</span>';
                    $qty = $product->get_stock_quantity();
                    $qty_text = $qty !== null ? $qty : 'N/A';
                    $edit_link = admin_url( 'post.php?post=' . ( $item['variation_id'] ? $item['product_id'] : $product->get_id() ) . '&action=edit' );
                    return sprintf(
                        '<strong><a href="%s">%s</a></strong><br><small>Статус: %s<br>Остаток: %s</small>',
                        esc_url( $edit_link ),
                        esc_html( $product->get_name() ),
                        $stock_status,
                        $qty_text
                    );
                }
                return 'Товар удален';
            case 'registered':
                return $item['user_id'] ? '<span style="background:#c6e1c6; color:#5b841b; padding:2px 6px; border-radius:3px; font-size:11px; font-weight:bold;">Yes</span>' : '';
            case 'created_at':
                return wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $item['created_at'] ) );
            default:
                return print_r( $item, true );
        }
    }

    public function column_cb( $item ) {
        return sprintf(
            '<input type="checkbox" name="subscriber[]" value="%s" />', $item['id']
        );
    }

    public function get_bulk_actions() {
        return [
            'delete' => 'Удалить'
        ];
    }

    public function process_bulk_action() {
        if ( 'delete' === $this->current_action() ) {
            $ids = isset( $_REQUEST['subscriber'] ) ? $_REQUEST['subscriber'] : [];
            if ( is_array( $ids ) && ! empty( $ids ) ) {
                global $wpdb;
                $table = $wpdb->prefix . 'wc_stock_notifications';
                $ids = array_map( 'absint', $ids );
                $wpdb->query( "DELETE FROM $table WHERE id IN (" . implode( ',', $ids ) . ")" );
            }
        }
    }

    public function prepare_items() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wc_stock_notifications';

        $this->process_bulk_action();

        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array( $columns, $hidden, $sortable );

        $per_page = 20;
        $current_page = $this->get_pagenum();
        
        $orderby = isset($_GET['orderby']) ? sanitize_sql_orderby($_GET['orderby']) : 'id';
        $order = isset($_GET['order']) && $_GET['order'] === 'asc' ? 'ASC' : 'DESC';

        $total_items = $wpdb->get_var( "SELECT COUNT(id) FROM $table_name" );

        $this->set_pagination_args( [
            'total_items' => $total_items,
            'per_page'    => $per_page
        ] );

        $offset = ( $current_page - 1 ) * $per_page;
        $this->items = $wpdb->get_results( "SELECT * FROM $table_name ORDER BY $orderby $order LIMIT $per_page OFFSET $offset", ARRAY_A );
    }
}

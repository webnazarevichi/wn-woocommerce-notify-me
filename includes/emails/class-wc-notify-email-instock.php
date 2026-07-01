<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! class_exists( 'WC_Email' ) ) {
	return;
}
class WC_Notify_Email_Instock extends WC_Email {
	public function __construct() {
		$this->id             = 'wc_notify_instock';
		$this->title          = 'Товар в наличии (Лист ожидания)';
		$this->description    = 'Это письмо отправляется клиенту, когда товар, на который он подписан, появляется в наличии.';
		$this->template_html  = 'emails/notify-instock.php';
		$this->template_plain = 'emails/plain/notify-instock.php';
		$this->template_base  = WC_NOTIFY_PLUGIN_DIR . 'templates/';
		$this->customer_email = true;
		parent::__construct();
		$this->placeholders = array(
			'{product_title}' => '',
		);
	}
	public function trigger( $sub_id ) {
		$this->setup_locale();
		global $wpdb;
        $table = $wpdb->prefix . 'wc_stock_notifications';
        $sub = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $sub_id ) );
        if ( ! $sub ) return;
        $this->object = $sub;
        $this->product = wc_get_product( $sub->variation_id ? $sub->variation_id : $sub->product_id );
        if ( ! $this->product ) return;
        $this->recipient = $sub->email;
        $this->placeholders['{product_title}'] = $this->product->get_name();
		if ( $this->is_enabled() && $this->get_recipient() ) {
			$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
		}
		$this->restore_locale();
	}
	public function get_default_subject() {
		return 'Товар {product_title} снова в наличии!';
	}
	public function get_default_heading() {
		return 'Товар снова в наличии';
	}
	public function get_content_html() {
		$product = $this->product;
		if ( ! is_object( $product ) ) {
			$products = wc_get_products( array( 'limit' => 1, 'status' => 'publish' ) );
			if ( ! empty( $products ) ) {
				$product = $products[0];
			} else {
				$product = new WC_Product_Simple();
				$product->set_id( 0 );
				$product->set_name( 'Пример товара' );
			}
		}
		return wc_get_template_html(
			$this->template_html,
			array(
				'sub'           => $this->object,
				'product'       => $product,
				'email_heading' => $this->get_heading(),
				'sent_to_admin' => false,
				'plain_text'    => false,
				'email'         => $this,
			),
			'',
			$this->template_base
		);
	}
	public function get_content_plain() {
		$product = $this->product;
		if ( ! is_object( $product ) ) {
			$products = wc_get_products( array( 'limit' => 1, 'status' => 'publish' ) );
			if ( ! empty( $products ) ) {
				$product = $products[0];
			} else {
				$product = new WC_Product_Simple();
				$product->set_id( 0 );
				$product->set_name( 'Пример товара' );
			}
		}
		return wc_get_template_html(
			$this->template_plain,
			array(
				'sub'           => $this->object,
				'product'       => $product,
				'email_heading' => $this->get_heading(),
				'sent_to_admin' => false,
				'plain_text'    => true,
				'email'         => $this,
			),
			'',
			$this->template_base
		);
	}
    public function init_form_fields() {
		$this->form_fields = array(
			'enabled' => array(
				'title'   => 'Включить/Выключить',
				'type'    => 'checkbox',
				'label'   => 'Включить это уведомление',
				'default' => 'yes',
			),
			'subject' => array(
				'title'       => 'Тема письма',
				'type'        => 'text',
				'desc_tip'    => true,
				'description' => sprintf( 'Доступные плейсхолдеры: %s', '<code>{product_title}</code>' ),
				'placeholder' => $this->get_default_subject(),
				'default'     => '',
			),
			'heading' => array(
				'title'       => 'Заголовок письма',
				'type'        => 'text',
				'desc_tip'    => true,
				'description' => sprintf( 'Доступные плейсхолдеры: %s', '<code>{product_title}</code>' ),
				'placeholder' => $this->get_default_heading(),
				'default'     => '',
			),
			'email_type' => array(
				'title'       => 'Тип письма',
				'type'        => 'select',
				'description' => 'Выберите формат отправляемого письма.',
				'default'     => 'html',
				'class'       => 'email_type wc-enhanced-select',
				'options'     => $this->get_email_type_options(),
				'desc_tip'    => true,
			),
		);
	}
}

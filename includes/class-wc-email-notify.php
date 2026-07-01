<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WC_Email_Notify_In_Stock extends WC_Email {
    public function __construct() {
        $this->id             = 'wc_notify_in_stock';
        $this->title          = 'Уведомление о наличии';
        $this->description    = 'Это письмо отправляется клиентам, когда товар снова появляется в наличии.';
        $this->template_html  = 'emails/wc-notify-in-stock.php';
        $this->template_plain = 'emails/plain/wc-notify-in-stock.php';
        $this->placeholders   = array(
            '{product_name}' => '',
            '{product_url}'  => '',
        );

        // Call parent constructor
        parent::__construct();
    }

    public function trigger( $subscriber_email, $product ) {
        $this->setup_locale();
        $this->recipient = $subscriber_email;
        $this->placeholders['{product_name}'] = $product->get_name();
        $this->placeholders['{product_url}']  = $product->get_permalink();

        if ( $this->is_enabled() && $this->get_recipient() ) {
            $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
        }
        $this->restore_locale();
    }

    public function get_content_html() {
        return wc_get_template_html( $this->template_html, array(
            'email_heading' => $this->get_heading(),
            'product_name'  => $this->placeholders['{product_name}'],
            'product_url'   => $this->placeholders['{product_url}'],
            'sent_to_admin' => false,
            'plain_text'    => false,
            'email'         => $this
        ), '', WC_NOTIFY_PLUGIN_DIR );
    }

    // Инициализация настроек админки
    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title'   => 'Включить/Выключить',
                'type'    => 'checkbox',
                'label'   => 'Включить это уведомление',
                'default' => 'yes'
            ),
            'subject' => array(
                'title'       => 'Тема',
                'type'        => 'text',
                'description' => sprintf( 'По умолчанию: <code>%s</code>', $this->get_default_subject() ),
                'placeholder' => $this->get_default_subject(),
                'default'     => 'Товар {product_name} снова в наличии!'
            ),
            'heading' => array(
                'title'       => 'Заголовок письма',
                'type'        => 'text',
                'default'     => 'Отличные новости!'
            ),
        );
    }
}
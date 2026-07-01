<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
do_action( 'woocommerce_email_header', $email_heading, $email );
?>
<p>Здравствуйте!</p>
<p>Вы успешно подписались на уведомление о появлении товара <strong><?php echo esc_html( $product->get_name() ); ?></strong>.</p>
<p>Мы отправим вам письмо, как только товар появится в наличии.</p>
<?php
do_action( 'woocommerce_email_footer', $email );

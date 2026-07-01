<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
echo "= " . $email_heading . " =\n\n";
echo "Здравствуйте!\n\n";
echo "Вы успешно подписались на уведомление о появлении товара " . $product->get_name() . ".\n";
echo "Мы отправим вам письмо, как только товар появится в наличии.\n\n";
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";
echo apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) );

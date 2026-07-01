<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
echo "= " . $email_heading . " =\n\n";
echo "Здравствуйте!\n\n";
echo "Товар " . $product->get_name() . ", который вы ждали, снова появился в наличии!\n\n";
echo "Перейти к товару: " . $product->get_permalink() . "\n\n";
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";
echo apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) );

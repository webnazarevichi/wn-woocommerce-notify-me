<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
do_action( 'woocommerce_email_header', $email_heading, $email );
?>
<p>Здравствуйте!</p>
<p>Товар <strong><?php echo esc_html( $product->get_name() ); ?></strong>, который вы ждали, снова появился в наличии!</p>
<p>
    <a href="<?php echo esc_url( $product->get_permalink() ); ?>" style="display:inline-block; padding:10px 20px; background-color:#96588a; color:#ffffff; text-decoration:none; border-radius:3px;">
        Перейти к товару
    </a>
</p>
<?php
do_action( 'woocommerce_email_footer', $email );

<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<table class="form-table">
	<a href="https://paymentservices.amazon.com/" target="_blank"><?php esc_html_e( 'Click here to sign up for Amazon Payment Services account', 'amazon-payment-services' ); ?></a>
	<?php
		$obj->generate_settings_html();
	?>
</table>

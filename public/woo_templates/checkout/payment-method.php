<?php
/**
 * Output a single payment method
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/payment-method.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see         https://docs.woocommerce.com/document/template-structure/
 * @package     WooCommerce\Templates
 * @version     3.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<?php
if ( APS_Constants::APS_PAYMENT_TYPE_APPLE_PAY === $gateway->id ) {
	?>
	<div class="wc_payment_method payment_method_<?php echo esc_attr( $gateway->id ); ?> apple_pay_option hide-me">
		<input id="payment_method_<?php echo esc_attr( $gateway->id ); ?>" type="radio" class="input-radio aps-d-none" name="payment_method" value="<?php echo esc_attr( $gateway->id ); ?>" data-order_button_text="<?php echo esc_attr( $gateway->order_button_text ); ?>" checked />
		<?php if ( $gateway->has_fields() || $gateway->get_description() ) : ?>
			<div class="apple-box">
				<h3 class="aps-margin-none"> </h3>
				<?php $gateway->payment_fields(); ?>
			</div>
		<?php endif; ?>
	</div>
	<?php
} else {
	?>
	<li class="wc_payment_method payment_method_<?php echo esc_attr( $gateway->id ); ?>">
		<input id="payment_method_<?php echo esc_attr( $gateway->id ); ?>" type="radio" class="input-radio" name="payment_method" value="<?php echo esc_attr( $gateway->id ); ?>" data-order_button_text="<?php echo esc_attr( $gateway->order_button_text ); ?>" />
		<label for="payment_method_<?php echo esc_attr( $gateway->id ); ?>">
			<?php echo APS_Constants::APS_PAYMENT_TYPE_CC == $gateway->id ? esc_html($gateway->get_filter_title()) : esc_html($gateway->get_title()); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?> <?php echo wp_kses_post($gateway->get_icon()); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?>
		</label>
		<?php if ( $gateway->has_fields() || $gateway->get_description() ) : ?>
			<?php
			$style_for_hide = '';
			if ( ! $gateway->chosen ) {
				$style_for_hide = 'style="display:none;"';
			} 
			?>
			<div class="payment_box payment_method_<?php echo esc_attr( $gateway->id ); ?>" <?php echo wp_kses_post($style_for_hide); ?>>
				<?php $gateway->payment_fields(); ?>
			</div>
		<?php endif; ?>
	</li>
	<?php
}
?>

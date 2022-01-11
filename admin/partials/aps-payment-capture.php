<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="aps_payment_authorization_history">
	<table class="wc-order-totals aps-table" border="1px">
		<tbody>
			<tr>
				<td class="label"><?php echo esc_html__( 'Order Total', 'amazon-payment-services' ); ?>:</td>
				<td class="total"></span><?php echo wp_kses_data(wc_price( $order_total )); ?></span></td>
			</tr>
			<?php if ( empty( $aps_authorization_action ) || 'CAPTURE' == $aps_authorization_action ) { ?>
				<tr>
					<td class="label"><?php echo esc_html__( 'Total Capture', 'amazon-payment-services' ); ?>:</td>
					<td class="total"></span><?php echo wp_kses_data(wc_price( $total_captured )); ?></span></td>
				</tr>
				<tr>
					<td class="label"><?php echo esc_html__( 'Remaining Capture', 'amazon-payment-services' ); ?>:</td>
					<td class="total"></span><?php echo wp_kses_data(wc_price( $remain_capture )); ?></span></td>
				</tr>
			<?php } ?>
			<?php if ( empty( $aps_authorization_action ) || 'VOID_AUTHORIZATION' == $aps_authorization_action ) { ?>
				<tr>
					<td class="label"><?php echo esc_html__( 'Void', 'amazon-payment-services' ); ?>:</td>
					<td class="total"></span><?php echo wp_kses_data(wc_price( $total_void )); ?></span></td>
				</tr>
			<?php } ?>
		</tbody>
	</table>		
</div>
<?php if ( ! empty( $aps_capture_history ) ) { ?>
	<div class="aps_payment_authorization_history">
		<h3><?php echo esc_html__( 'History of Capture/Void', 'amazon-payment-services' ); ?></h3>
		<table class="wc-order-totals aps-table" border="1px">
			<thead>
				<tr>
					<th><?php echo esc_html__( 'Date', 'amazon-payment-services' ); ?></th>
					<th><?php echo esc_html__( 'Amount', 'amazon-payment-services' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $aps_capture_history as $key => $history ) { ?>
					<tr>
						<td class="total"></span><?php echo esc_attr($history['date']); ?></span></td>
						<td class="total"></span><?php echo wp_kses_data(wc_price( $history['amount'] )); ?></span></td>
					</tr>
				<?php } ?>
			</tbody>
		</table>		
	</div>
	<?php
}
if ( empty( $aps_authorization_action ) || ( 'CAPTURE' == $aps_authorization_action && $remain_capture > 0 ) ) {
	?>
	<div class="aps_payment_authorization">
		<div class="aps_payment_action_error hidden aps_error"></div>
		<select name="aps_authorization_command" id="aps_authorization_command">
				<option value="" selected="selected"><?php echo esc_html__( 'Select Action', 'amazon-payment-services' ); ?></option>
				<option value="CAPTURE" ><?php echo esc_html__( 'Capture', 'amazon-payment-services' ); ?></option>
				
				<?php if ( ! ( $total_captured > 0 ) ) { ?>
				<option value="VOID_AUTHORIZATION" ><?php echo esc_html__( 'Void', 'amazon-payment-services' ); ?></option>
				<?php } ?>
		</select>
		<input type="hidden" name="remain_capture" id="remain_capture" value="<?php echo esc_attr($remain_capture); ?>">
		<input type="text" placeholder="<?php echo esc_html__( 'Enter Amout', 'amazon-payment-services' ); ?>" id="amount_authorization" name="amount_authorization" value='<?php echo esc_attr($remain_capture); ?>' class="short wc_input_price text-box" style="width: 220px; display: none;">

		<input type="hidden" value="no" name="is_submited" id="is_submited">
		<input type="submit" id="aps_payment_submit_button" value="Submit" name="save" class="button button-primary">
	</div>
<?php } else { ?>
	<div class="aps_payment_authorization_detail">
		<?php if ( 'CAPTURE' == $aps_authorization_action ) { ?>
			<h3><?php echo esc_html__( 'Payment Captured', 'amazon-payment-services' ); ?></h3>
		<?php } else { ?>
			<h3><?php echo esc_html__( 'Authorization Voided', 'amazon-payment-services' ); ?></h3>
		<?php } ?>
	</div>
<?php } ?>

<div class="aps_payment_authorization_history">
	<table class="wc-order-totals aps-table" border="1px">
		<tbody>
			<tr>
				<td class="label"><?php echo __( 'Order Total', 'amazon_payment_services' ); ?>:</td>
				<td class="total"></span><?php echo wc_price( $order_total ); ?></span></td>
			</tr>
			<?php if ( empty( $aps_authorization_action ) || $aps_authorization_action == 'CAPTURE' ) { ?>
				<tr>
					<td class="label"><?php echo __( 'Total Capture', 'amazon_payment_services' ); ?>:</td>
					<td class="total"></span><?php echo wc_price( $total_captured ); ?></span></td>
				</tr>
				<tr>
					<td class="label"><?php echo __( 'Remaining Capture', 'amazon_payment_services' ); ?>:</td>
					<td class="total"></span><?php echo wc_price( $remain_capture ); ?></span></td>
				</tr>
			<?php } ?>
			<?php if ( empty( $aps_authorization_action ) || $aps_authorization_action == 'VOID_AUTHORIZATION' ) { ?>
				<tr>
					<td class="label"><?php echo __( 'Void', 'amazon_payment_services' ); ?>:</td>
					<td class="total"></span><?php echo wc_price( $total_void ); ?></span></td>
				</tr>
			<?php } ?>
		</tbody>
	</table>		
</div>
<?php if ( ! empty( $aps_capture_history ) ) { ?>
	<div class="aps_payment_authorization_history">
		<h3><?php echo __( 'History of Capture/Void', 'amazon_payment_services' ); ?></h3>
		<table class="wc-order-totals aps-table" border="1px">
			<thead>
				<tr>
					<th><?php echo __( 'Date', 'amazon_payment_services' ); ?></th>
					<th><?php echo __( 'Amount', 'amazon_payment_services' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $aps_capture_history as $key => $history ) { ?>
					<tr>
						<td class="total"></span><?php echo $history['date']; ?></span></td>
						<td class="total"></span><?php echo wc_price( $history['amount'] ); ?></span></td>
					</tr>
				<?php } ?>
			</tbody>
		</table>		
	</div>
	<?php
}
if ( empty( $aps_authorization_action ) || ( $aps_authorization_action == 'CAPTURE' && $remain_capture > 0 ) ) {
	?>
	<div class="aps_payment_authorization">
		<div class="aps_payment_action_error hidden aps_error"></div>
		<select name="aps_authorization_command" id="aps_authorization_command">
				<option value="" selected="selected"><?php echo __( 'Select Action', 'amazon_payment_services' ); ?></option>
				<option value="CAPTURE" ><?php echo __( 'Capture', 'amazon_payment_services' ); ?></option>
				
				<?php if ( ! ( $total_captured > 0 ) ) { ?>
				<option value="VOID_AUTHORIZATION" ><?php echo __( 'Void', 'amazon_payment_services' ); ?></option>
				<?php } ?>
		</select>
		<input type="hidden" name="remain_capture" id="remain_capture" value="<?php echo $remain_capture; ?>">
		<input type="text" placeholder="<?php echo __( 'Enter Amout', 'amazon_payment_services' ); ?>" id="amount_authorization" name="amount_authorization" value='<?php echo $remain_capture; ?>' class="short wc_input_price text-box" style="width: 220px; display: none;">

		<input type="hidden" value="no" name="is_submited" id="is_submited">
		<input type="submit" id="aps_payment_submit_button" value="Submit" name="save" class="button button-primary">
	</div>
<?php } else { ?>
	<div class="aps_payment_authorization_detail">
		<?php if ( $aps_authorization_action == 'CAPTURE' ) { ?>
			<h3><?php echo __( 'Payment Captured', 'amazon_payment_services' ); ?></h3>
		<?php } else { ?>
			<h3><?php echo __( 'Authorization Voided', 'amazon_payment_services' ); ?></h3>
		<?php } ?>
	</div>
<?php } ?>

<?php
if ( isset( $_GET['delete_certificate'] ) ) {
	$certificate_path = plugin_dir_path( dirname( dirname( __FILE__ ) ) ) . 'certificates/';
	if ( 'pathfile' === $_GET['delete_certificate'] ) {
		$existing_apple_certificates = get_option( 'aps_apple_pay_certificates' );
		if ( isset( $existing_apple_certificates['apple_certificate_path_file'] ) ) {
			if ( file_exists( $certificate_path . $existing_apple_certificates['apple_certificate_path_file'] ) ) {
				unlink( $certificate_path . $existing_apple_certificates['apple_certificate_path_file'] );
			}
			unset( $existing_apple_certificates['apple_certificate_path_file'] );
			update_option( 'aps_apple_pay_certificates', $existing_apple_certificates );
			$_SESSION['aps_success_message'] = 'Certificate deleted successfully';
		}
	} elseif ( 'keyfile' === $_GET['delete_certificate'] ) {
		$existing_apple_certificates = get_option( 'aps_apple_pay_certificates' );
		if ( isset( $existing_apple_certificates['apple_certificate_key_file'] ) ) {
			if ( file_exists( $certificate_path . $existing_apple_certificates['apple_certificate_key_file'] ) ) {
				unlink( $certificate_path . $existing_apple_certificates['apple_certificate_key_file'] );
			}
			unset( $existing_apple_certificates['apple_certificate_key_file'] );
			update_option( 'aps_apple_pay_certificates', $existing_apple_certificates );
			if ( file_exists( $certificate_path . $existing_apple_certificates['apple_certificate_key_file'] ) ) {
				unlink( $certificate_path . $existing_apple_certificates['apple_certificate_key_file'] );
			}
			$_SESSION['aps_success_message'] = 'Certificate deleted successfully';
		}
	}
	wp_safe_redirect( admin_url( 'options-general.php?page=apple-pay-certificates' ) );
}
$apple_certificates = get_option( 'aps_apple_pay_certificates' );
?>
<div id="adminWrapper">
	<form method="POST" class="apple_pay" action="<?php echo esc_url_raw( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
		<input name='action' type="hidden" value='upload_apple_certificates'>
		<?php wp_nonce_field( 'upload_apple_certificates', '_upload_apple_certificates_nonce' ); ?>
		<div class="form-row">
			<label><?php echo __( 'Certificate File', 'amazon_payment_services' ); ?></label>
			<input type="file" name="certificate_path_file" <?php echo ! isset( $apple_certificates['apple_certificate_path_file'] ) ? 'required' : ''; ?>/>
			<?php
			if ( isset( $apple_certificates['apple_certificate_path_file'] ) ) {
				echo '<label> Existing : ' . wp_kses_data( $apple_certificates['apple_certificate_path_file'] ) . ' <a href="' . admin_url( 'options-general.php?page=apple-pay-certificates&delete_certificate=pathfile' ) . '" onclick="return confirm(\'Are you sure you want to delete this certificate?\')" class="delete_btn">Delete File</a></label>';
			}
			?>
		</div>

		<div class="form-row">
			<label><?php echo __( 'Certificate Key File', 'amazon_payment_services' ); ?></label>
			<input type="file" name="certificate_key_file" <?php echo ! isset( $apple_certificates['apple_certificate_key_file'] ) ? 'required' : ''; ?>/>
			<?php
			if ( isset( $apple_certificates['apple_certificate_key_file'] ) ) {
				echo '<label> Existing : ' . wp_kses_data( $apple_certificates['apple_certificate_key_file'] ) . ' <a href="' . admin_url( 'options-general.php?page=apple-pay-certificates&delete_certificate=keyfile' ) . '" onclick="return confirm(\'Are you sure you want to delete this certificate?\')" class="delete_btn">Delete File</a></label>';
			}
			?>
		</div>
		<input type="submit" name='save_cerificates' value='Submit' />
	</form>
</div>

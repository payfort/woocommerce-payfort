<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( isset( $_GET['delete_certificate'] ) ) {
    session_start();
    if ( isset($_GET['_delete_apple_certificates_nonce']) && wp_verify_nonce( sanitize_key($_GET['_delete_apple_certificates_nonce']), 'delete_apple_certificates' ) ) {
        $upload_dir = wp_upload_dir();
        $certificate_path = $upload_dir['basedir'] . '/aps-certificates/';
        if ('pathfile' === $_GET['delete_certificate']) {
            $existing_apple_certificates = get_option('aps_apple_pay_certificates');
            if (isset($existing_apple_certificates['apple_certificate_path_file'])) {
                if (file_exists($certificate_path . $existing_apple_certificates['apple_certificate_path_file'])) {
                    unlink($certificate_path . $existing_apple_certificates['apple_certificate_path_file']);
                }
                unset($existing_apple_certificates['apple_certificate_path_file']);
                update_option('aps_apple_pay_certificates', $existing_apple_certificates);
                $_SESSION['aps_success_message'] = wp_kses_data('Certificate deleted successfully');
            }
        } elseif ('keyfile' === $_GET['delete_certificate']) {
            $existing_apple_certificates = get_option('aps_apple_pay_certificates');
            if (isset($existing_apple_certificates['apple_certificate_key_file'])) {
                if (file_exists($certificate_path . $existing_apple_certificates['apple_certificate_key_file'])) {
                    unlink($certificate_path . $existing_apple_certificates['apple_certificate_key_file']);
                }
                unset($existing_apple_certificates['apple_certificate_key_file']);
                update_option('aps_apple_pay_certificates', $existing_apple_certificates);
                if (file_exists($certificate_path . $existing_apple_certificates['apple_certificate_key_file'])) {
                    unlink($certificate_path . $existing_apple_certificates['apple_certificate_key_file']);
                }
                $_SESSION['aps_success_message'] = wp_kses_data('Certificate deleted successfully');
            }
        }
    } else {
        $_SESSION['aps_error_messages'] = array_merge(
            (array)$_SESSION['aps_error_messages'],
            [
                wp_kses_data('Failed to delete certificate! Please try again'),
            ]
        );
    }
    session_write_close();
    wp_safe_redirect(admin_url('options-general.php?page=apple-pay-certificates'));
}

$apple_certificates = get_option( 'aps_apple_pay_certificates' );
?>
<div id="adminWrapper">
    <form method="POST" class="apple_pay" action="<?php echo esc_url_raw( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
        <input name='action' type="hidden" value='upload_apple_certificates'>
        <?php wp_nonce_field( 'upload_apple_certificates', '_upload_apple_certificates_nonce' ); ?>
        <div class="form-row">
            <label><?php echo esc_html__( 'Certificate File', 'amazon-payment-services' ); ?></label>
            <input type="file" name="certificate_path_file" <?php echo ! isset( $apple_certificates['apple_certificate_path_file'] ) ? 'required' : ''; ?>/>
            <?php
            if ( isset( $apple_certificates['apple_certificate_path_file'] ) ) {
                echo '<label> Existing : ' . wp_kses_data( $apple_certificates['apple_certificate_path_file'] ) . ' <a href="' . wp_nonce_url(admin_url( 'options-general.php?page=apple-pay-certificates&delete_certificate=pathfile' ), 'delete_apple_certificates', '_delete_apple_certificates_nonce') . '" onclick="return confirm(\'Are you sure you want to delete this certificate?\')" class="delete_btn">Delete File</a></label>';
            }
            ?>
        </div>

        <div class="form-row">
            <label><?php echo esc_html__( 'Certificate Key File', 'amazon-payment-services' ); ?></label>
            <input type="file" name="certificate_key_file" <?php echo ! isset( $apple_certificates['apple_certificate_key_file'] ) ? 'required' : ''; ?>/>
            <?php
            if ( isset( $apple_certificates['apple_certificate_key_file'] ) ) {
                echo '<label> Existing : ' . wp_kses_data( $apple_certificates['apple_certificate_key_file'] ) . ' <a href="' . wp_nonce_url(admin_url( 'options-general.php?page=apple-pay-certificates&delete_certificate=keyfile' ), 'delete_apple_certificates', '_delete_apple_certificates_nonce') . '" onclick="return confirm(\'Are you sure you want to delete this certificate?\')" class="delete_btn">Delete File</a></label>';
            }
            ?>
        </div>
        <input type="submit" name='save_cerificates' value='Submit' />
    </form>
</div>

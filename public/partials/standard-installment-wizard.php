<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
echo '<div class="pf-iframe-background" id="div-pf-iframe" style="display:none">
<div class="pf-iframe-container">
    <span class="pf-close-container">
        <a href="' . esc_url_raw( create_wc_api_url( 'aps_merchant_cancel' ) ) . '"><i class="fa fa-times-circle pf-iframe-close"></i></a>
    </span>
    <i class="fa fa-spinner fa-spin pf-iframe-spin"></i>
    <div class="pf-iframe" id="pf_iframe_content"></div>
</div>
</div><div class="form_box"></div>';

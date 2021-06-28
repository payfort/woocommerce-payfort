<?php
echo '<input type="hidden" id="aps_visa_checkout_status" name="aps_visa_checkout_status" value="" />';
echo '<input type="hidden" id="aps_visa_checkout_callid" name="aps_visa_checkout_callid" value="" />';
echo '<img alt="Visa Checkout" class="v-button" role="button" src=" ' . wp_kses_data( $visa_checkout_button_url ) . '?cardBrands=VISA,MASTERCARD,DISCOVER,AMEX" />';
echo '<script type="text/javascript" src="' . wp_kses_data( $visa_checkout_sdk ) . '"></script>';

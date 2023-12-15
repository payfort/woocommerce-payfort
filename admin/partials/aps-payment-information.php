<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$table_data   = array();
$table_data[] = array(
	'label' => __( 'Command', 'amazon-payment-services' ),
	'value' => $aps_data['command'],
);
$table_data[] = array(
	'label' => __( 'Merchant Reference', 'amazon-payment-services' ),
	'value' => $aps_data['merchant_reference'],
);
$table_data[] = array(
	'label' => __( 'Fort ID', 'amazon-payment-services' ),
	'value' => $aps_data['fort_id'],
);
$table_data[] = array(
	'label' => __( 'Payment Option', 'amazon-payment-services' ),
	'value' => $aps_data['payment_option'],
);
if ( APS_Constants::APS_PAYMENT_TYPE_INSTALLMENT === $payment_method ) {
	$installment_amount   = get_post_meta( $order_id, 'aps_installment_amount', true );
	$installment_interest = get_post_meta( $order_id, 'aps_installment_interest', true );
	if ( isset( $aps_data['number_of_installments'] ) && ! empty( $aps_data['number_of_installments'] ) ) {
		$table_data[] = array(
			'label' => __( 'Installments', 'amazon-payment-services' ),
			'value' => $aps_data['number_of_installments'],
		);
	}
	if ( ! empty( $installment_amount ) ) {
		$table_data[] = array(
			'label' => __( 'Installment Amount', 'amazon-payment-services' ),
			'value' => $installment_amount,
		);
	}
	if ( ! empty( $installment_interest ) ) {
		$table_data[] = array(
			'label' => __( 'Installment Interest', 'amazon-payment-services' ),
			'value' => $installment_interest,
		);
	}
} elseif ( APS_Constants::APS_PAYMENT_TYPE_CC === $payment_method ) {
	$installment_amount   = get_post_meta( $order_id, 'aps_cc_amount', true );
	$installment_interest = get_post_meta( $order_id, 'aps_cc_interest', true );
	if ( isset( $aps_data['number_of_installments'] ) && ! empty( $aps_data['number_of_installments'] ) ) {
		$table_data[] = array(
			'label' => __( 'Installments', 'amazon-payment-services' ),
			'value' => $aps_data['number_of_installments'],
		);
	}
	if ( ! empty( $installment_amount ) ) {
		$table_data[] = array(
			'label' => __( 'Installment Amount', 'amazon-payment-services' ),
			'value' => $installment_amount,
		);
	}
	if ( ! empty( $installment_interest ) ) {
		$table_data[] = array(
			'label' => __( 'Installment Interest', 'amazon-payment-services' ),
			'value' => $installment_interest,
		);
	}
} elseif ( APS_Constants::APS_PAYMENT_TYPE_VALU === $payment_method ) {
	$tenure          = get_post_meta( $order_id, 'valu_active_tenure', true );
	$tenure_amount   = get_post_meta( $order_id, 'valu_tenure_amount', true );
	$tenure_interest = get_post_meta( $order_id, 'valu_tenure_interest', true );
	$down_payment = get_post_meta( $order_id, 'valu_down_payment', true );
	$tou = get_post_meta( $order_id, 'valu_tou', true );
	$cash_back = get_post_meta( $order_id, 'valu_cash_back', true );
    $valu_transaction_id = get_post_meta( $order_id, 'valu_transaction_id', true );
    $loan_number = get_post_meta( $order_id, 'loan_number', true );

    if ( ! empty( $tenure ) ) {
		$table_data[] = array(
			'label' => __( 'Tenure', 'amazon-payment-services' ),
			'value' => $tenure,
		);
	}
	if ( ! empty( $tenure_amount ) ) {
		$table_data[] = array(
			'label' => __( 'Tenure Amount', 'amazon-payment-services' ),
			'value' => $tenure_amount . ' ' . $aps_data['currency'],
		);
	}
	if ( ! empty( $tenure_interest ) ) {
        $table_data[] = array(
            'label' => __('Admin Fee', 'amazon-payment-services'),
            'value' => $tenure_interest . '',
        );
    }
    if ( ! empty( $down_payment ) ) {
        $table_data[] = array(
            'label' => __('Down Payment', 'amazon-payment-services'),
            'value' => $down_payment . '',
        );
    }
    if ( ! empty( $tou ) ) {
        $table_data[] = array(
            'label' => __('ToU', 'amazon-payment-services'),
            'value' => $tou . '',
        );
    }
    if ( ! empty( $cash_back ) ) {
		$table_data[] = array(
			'label' => __( 'Cash Back', 'amazon-payment-services' ),
			'value' => $cash_back . '',
		);
	}
    if ( ! empty( $valu_transaction_id ) ) {
        $table_data[] = array(
            'label' => __( 'Transaction ID', 'amazon-payment-services' ),
            'value' => $valu_transaction_id . '',
        );
    }
    if ( ! empty( $loan_number ) ) {
        $table_data[] = array(
            'label' => __( 'Loan Number', 'amazon-payment-services' ),
            'value' => $loan_number . '',
        );
    }
}elseif (APS_Constants::APS_PAYMENT_TYPE_STC_PAY === $payment_method){
    if ( isset( $aps_data['amount'] ) && ! empty( $aps_data['amount'] ) ) {
        $table_data[] = array(
            'label' => __( 'Amount', 'amazon-payment-services' ),
            'value' => $aps_data['amount'],
        );
    }

    if ( isset( $aps_data['digital_wallet'] ) && ! empty( $aps_data['digital_wallet'] ) ) {
        $table_data[] = array(
            'label' => __( 'Digital Wallet', 'amazon-payment-services' ),
            'value' => $aps_data['digital_wallet'],
        );
    }

    if ( isset( $aps_data['signature'] ) && ! empty( $aps_data['signature'] ) ) {
        $table_data[] = array(
            'label' => __( 'Signature', 'amazon-payment-services' ),
            'value' => $aps_data['signature'],
        );
    }

    if ( isset( $aps_data['access_code'] ) && ! empty( $aps_data['access_code'] ) ) {
        $table_data[] = array(
            'label' => __( 'Access Code', 'amazon-payment-services' ),
            'value' => $aps_data['access_code'],
        );
    }

    if ( isset( $aps_data['language'] ) && ! empty( $aps_data['language'] ) ) {
        $table_data[] = array(
            'label' => __( 'language', 'amazon-payment-services' ),
            'value' => $aps_data['language'],
        );
    }

    if ( isset( $aps_data['eci'] ) && ! empty( $aps_data['eci'] ) ) {
        $table_data[] = array(
            'label' => __( 'ECI', 'amazon-payment-services' ),
            'value' => $aps_data['eci'],
        );
    }

    if ( isset( $aps_data['response_message'] ) && ! empty( $aps_data['response_message'] ) ) {
        $table_data[] = array(
            'label' => __( 'Response Message', 'amazon-payment-services' ),
            'value' => $aps_data['response_message'],
        );
    }

    if ( isset( $aps_data['currency'] ) && ! empty( $aps_data['currency'] ) ) {
        $table_data[] = array(
            'label' => __( 'Currency', 'amazon-payment-services' ),
            'value' => $aps_data['currency'],
        );
    }

    if ( isset( $aps_data['customer_name'] ) && ! empty( $aps_data['customer_name'] ) ) {
        $table_data[] = array(
            'label' => __( 'Customer Name', 'amazon-payment-services' ),
            'value' => $aps_data['customer_name'],
        );
    }

    if ( isset( $aps_data['status'] ) && ! empty( $aps_data['status'] ) ) {
        $table_data[] = array(
            'label' => __( 'Status', 'amazon-payment-services' ),
            'value' => $aps_data['status'],
        );
    }
}
elseif (APS_Constants::APS_PAYMENT_TYPE_TABBY === $payment_method){
    if ( isset( $aps_data['amount'] ) && ! empty( $aps_data['amount'] ) ) {
        $table_data[] = array(
            'label' => __( 'Amount', 'amazon-payment-services' ),
            'value' => $aps_data['amount'],
        );
    }

    if ( isset( $aps_data['signature'] ) && ! empty( $aps_data['signature'] ) ) {
        $table_data[] = array(
            'label' => __( 'Signature', 'amazon-payment-services' ),
            'value' => $aps_data['signature'],
        );
    }

    if ( isset( $aps_data['access_code'] ) && ! empty( $aps_data['access_code'] ) ) {
        $table_data[] = array(
            'label' => __( 'Access Code', 'amazon-payment-services' ),
            'value' => $aps_data['access_code'],
        );
    }

    if ( isset( $aps_data['language'] ) && ! empty( $aps_data['language'] ) ) {
        $table_data[] = array(
            'label' => __( 'language', 'amazon-payment-services' ),
            'value' => $aps_data['language'],
        );
    }

    if ( isset( $aps_data['eci'] ) && ! empty( $aps_data['eci'] ) ) {
        $table_data[] = array(
            'label' => __( 'ECI', 'amazon-payment-services' ),
            'value' => $aps_data['eci'],
        );
    }

    if ( isset( $aps_data['response_message'] ) && ! empty( $aps_data['response_message'] ) ) {
        $table_data[] = array(
            'label' => __( 'Response Message', 'amazon-payment-services' ),
            'value' => $aps_data['response_message'],
        );
    }

    if ( isset( $aps_data['currency'] ) && ! empty( $aps_data['currency'] ) ) {
        $table_data[] = array(
            'label' => __( 'Currency', 'amazon-payment-services' ),
            'value' => $aps_data['currency'],
        );
    }

    if ( isset( $aps_data['customer_name'] ) && ! empty( $aps_data['customer_name'] ) ) {
        $table_data[] = array(
            'label' => __( 'Customer Name', 'amazon-payment-services' ),
            'value' => $aps_data['customer_name'],
        );
    }

    if ( isset( $aps_data['phone_number'] ) && ! empty( $aps_data['phone_number'] ) ) {
        $table_data[] = array(
            'label' => __( 'Phone Number', 'amazon-payment-services' ),
            'value' => $aps_data['phone_number'],
        );
    }

    if ( isset( $aps_data['status'] ) && ! empty( $aps_data['status'] ) ) {
        $table_data[] = array(
            'label' => __( 'Status', 'amazon-payment-services' ),
            'value' => $aps_data['status'],
        );
    }
}
if ( isset( $aps_data['token_name'] ) && ! empty( $aps_data['token_name'] ) ) {
	$table_data[] = array(
		'label' => __( 'Card Token', 'amazon-payment-services' ),
		'value' => $aps_data['token_name'],
	);
}
if ( isset( $aps_data['expiry_date'] ) && ! empty( $aps_data['expiry_date'] ) ) {
	$table_data[] = array(
		'label' => __( 'Card Expiry', 'amazon-payment-services' ),
		'value' => $aps_data['expiry_date'],
	);
}
if ( isset( $aps_data['card_number'] ) && ! empty( $aps_data['card_number'] ) ) {
	$table_data[] = array(
		'label' => __( 'Card Number', 'amazon-payment-services' ),
		'value' => $aps_data['card_number'],
	);
}
if ( isset( $aps_data['authorization_code'] ) && ! empty( $aps_data['authorization_code'] ) ) {
	$table_data[] = array(
		'label' => __( 'Authorization Code', 'amazon-payment-services' ),
		'value' => $aps_data['authorization_code'],
	);
}
if ( isset( $aps_data['response_code'] ) && ! empty( $aps_data['response_code'] ) ) {
	$table_data[] = array(
		'label' => __( 'Response Code', 'amazon-payment-services' ),
		'value' => $aps_data['response_code'],
	);
}
if ( isset( $aps_data['acquirer_response_code'] ) && ! empty( $aps_data['acquirer_response_code'] ) ) {
	$table_data[] = array(
		'label' => __( 'Acquier Response Code', 'amazon-payment-services' ),
		'value' => $aps_data['acquirer_response_code'],
	);
}
if ( isset( $aps_data['reconciliation_reference'] ) && ! empty( $aps_data['reconciliation_reference'] ) ) {
	$table_data[] = array(
		'label' => __( 'Reconciliation Reference', 'amazon-payment-services' ),
		'value' => $aps_data['reconciliation_reference'],
	);
}
if ( isset( $aps_data['acquirer_response_message'] ) && ! empty( $aps_data['acquirer_response_message'] ) ) {
	$table_data[] = array(
		'label' => __( 'Acquirer Response Message', 'amazon-payment-services' ),
		'value' => $aps_data['acquirer_response_message'],
	);
}
if ( isset( $aps_data['customer_ip'] ) && ! empty( $aps_data['customer_ip'] ) ) {
	$table_data[] = array(
		'label' => __( 'Customer IP', 'amazon-payment-services' ),
		'value' => $aps_data['customer_ip'],
	);
}
if ( isset( $aps_data['customer_email'] ) && ! empty( $aps_data['customer_email'] ) ) {
	$table_data[] = array(
		'label' => __( 'Customer Email', 'amazon-payment-services' ),
		'value' => $aps_data['customer_email'],
	);
}
if ( isset( $aps_data['phone_number'] ) && ! empty( $aps_data['phone_number'] ) ) {
	$table_data[] = array(
		'label' => __( 'Phone Number', 'amazon-payment-services' ),
		'value' => $aps_data['phone_number'],
	);
}
if ( isset( $aps_data['third_party_transaction_number'] ) && ! empty( $aps_data['third_party_transaction_number'] ) ) {
	$table_data[] = array(
		'label' => __( 'third party transaction number (KNET, Benefit, OmanNet)', 'amazon-payment-services' ),
		'value' => $aps_data['third_party_transaction_number'],
	);
}
if ( isset( $aps_data['knet_ref_number'] ) && ! empty( $aps_data['knet_ref_number'] ) ) {
	$table_data[] = array(
		'label' => __( 'KNET ref number', 'amazon-payment-services' ),
		'value' => $aps_data['knet_ref_number'],
	);
}
?>
<table class="aps-table" border="1px">
	<thead>
		<tr>
			<th><?php echo esc_html__( 'Title', 'amazon-payment-services' ); ?></th>
			<th><?php echo esc_html__( 'Value', 'amazon-payment-services' ); ?></th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ( $table_data as $table_row ) { ?>
			<tr>
			<td><?php echo wp_kses_data( $table_row['label'] ); ?></td>
			<td><?php echo wp_kses_data( $table_row['value'] ); ?></td>
			</tr>
		<?php } ?>
	</tbody>
</table>


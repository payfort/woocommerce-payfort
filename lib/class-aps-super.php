<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class APS_Super extends WC_Payment_Gateway {

	public $id = APS_Constants::APS_PAYMENT_TYPE_CC;

	public function __construct() {

	}
}

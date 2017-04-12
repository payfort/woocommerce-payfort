<?php

class Payfort_Fort_Order extends Payfort_Fort_Super
{

    private $order = array();
    private $orderId;
    private $pfConfig;

    public function __construct()
    {
        parent::__construct();
        $this->pfConfig = Payfort_Fort_Config::getInstance();
    }

    public function loadOrder($orderId)
    {
        $this->orderId = $orderId;
        $this->order   = $this->getOrderById($orderId);
    }

    public function setOrderId($orderId)
    {
        $this->orderId = $orderId;
    }

    public function setOrder($order)
    {
        $this->order = $order;
    }

    public function getSessionOrderId()
    {
        return WC()->session->get('order_awaiting_payment');
    }

    public function getOrderId()
    {
        return $this->order->id;
    }

    public function getOrderById($orderId)
    {
        $order = wc_get_order($orderId);
        return $order;
    }

    public function getLoadedOrder()
    {
        return $this->order;
    }

    public function getEmail()
    {
        return $this->order->billing_email;
    }

    public function getCustomerName()
    {
        $fullName  = '';
        $firstName = $this->order->billing_first_name;
        $lastName  = $this->order->billing_last_name;

        $fullName = trim($firstName . ' ' . $lastName);
        return $fullName;
    }

    public function getCurrencyCode()
    {
        return $this->order->get_order_currency();
    }

    public function getCurrencyValue()
    {
        return 1;
    }

    public function getTotal()
    {
        return $this->order->get_total();
    }

    public function getPaymentMethod()
    {
        return $this->order->payment_method;
    }

    public function getStatusId()
    {
        return $this->order->get_status();
    }

    public function declineOrder($reason = '')
    {
        $status = 'failed';
        if(!$this->getOrderId()) {
            return true;
        }
        if ($this->getStatusId() == $status) {
            return true;
        }
        $note = 'Payment Failed';
        if(!empty($reason)) {
            $note .= " ($reason)";
        }
        
        //prevent send cancel order email
        remove_action( 'woocommerce_order_status_pending_to_cancelled', array( 'WC_Emails', 'send_transactional_email' ) );
        remove_action( 'woocommerce_order_status_pending_to_failed', array( 'WC_Emails', 'send_transactional_email' ) );
        
        $this->order->cancel_order($note);
        $this->order->update_status( $status, $note );
        unset( WC()->session->order_awaiting_payment );
        if($this->pfConfig->orderPlacementIsOnSuccess()) {
            $this->order->update_status( '', 'Hide Order' );
        }
        return true;
    }

    public function cancelOrder()
    {
        $status = 'cancelled';
        if(!$this->getOrderId()) {
            return true;
        }
        if ($this->getStatusId() == $status) {
            return true;
        }
        
        //prevent send cancel order email
        remove_action( 'woocommerce_order_status_pending_to_cancelled', array( 'WC_Emails', 'send_transactional_email' ) );
        remove_action( 'woocommerce_order_status_pending_to_failed', array( 'WC_Emails', 'send_transactional_email' ) );
        
        $this->order->cancel_order('Payment Cancelled');
        if($this->pfConfig->orderPlacementIsOnSuccess()) {
            $this->order->update_status( '', 'Hide Order' );
        }
        return true;
    }

    public function successOrder($response_params, $response_mode)
    {
        if ($this->getOrderId()) {
            $this->order->payment_complete();
            if(isset($response_params['fort_id']) && $response_mode == 'offline') {
                $this->order->add_order_note('Payfort payment successful<br/>Fort id: '.$response_params['fort_id']);
            }
        }
        return true;
    }

}

?>
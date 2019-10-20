<?php

/*
 * allow payment and cancellation of on-hold orders
 */


/*
 * Cheque or "other payment method" goes straight to on hold (which is better than pending
 * because the emails are issued) and is intended to allow offline payment
 * .. but allow the client to change their mind and pay online...
 *
 * @param Array $valid_order_statuses   order statuses in which this order can be paid for..
 * @param WC_Order $order       the current order
 *
 * @return Array                array of valid order status strings
 */
function inkston_allow_pay_onhold( $valid_order_statuses, $order ) {
	$valid_order_statuses[] = 'on-hold';
	return $valid_order_statuses;
}

//woocommerce_order_is_pending_statuses in 3.6 only has one parameter instead of two
//will be fixed in subsequent version
function inkston_allow_pay_onhold_one( $valid_order_statuses ) {
	$valid_order_statuses[] = 'on-hold';
	return $valid_order_statuses;
}

/*
 * woocommerce_order_is_pending_statuses is similar to woocommerce_valid_order_statuses_for_payment
 * but only used for rechecking stock before accepting payment: payment may be refused if 
 * stock is no longer available
 *
 * @param Array $valid_order_statuses   order statuses which count as pending..
 *
 * @return Array                array of valid order status strings
 */
function skip_stockcheck_pay_onhold( $valid_order_statuses ) {
	$valid_order_statuses[] = 'on-hold';
	return $valid_order_statuses;
}
add_filter( 'woocommerce_valid_order_statuses_for_payment', 'inkston_allow_pay_onhold', 10, 2 );
add_filter( 'woocommerce_order_is_pending_statuses', 'inkston_allow_pay_onhold_one', 10, 1 );

/*
 * custom statuses for shipped are not perceived as paid by woocommerce due to this filter
 */
function inkston_shipped_is_paid( $paid_order_statuses ) {
	$paid_order_statuses[] = 'shipped';
	return $paid_order_statuses;
}

//apply_filters( 'woocommerce_order_is_paid_statuses', array( 'processing', 'completed' ) );
add_filter( 'woocommerce_order_is_paid_statuses', 'inkston_shipped_is_paid', 10, 1 );


/*
 * .. allow the client to change their mind and cancel On Hold orders...
 *
 * @param Array $valid_order_statuses   order statuses in which this order can be paid for..
 *
 * @return Array                array of valid order status strings
 */
function inkston_allow_cancel_onhold( $valid_order_statuses ) {
	$valid_order_statuses[] = 'on-hold';
	return $valid_order_statuses;
}

add_filter( 'woocommerce_valid_order_statuses_for_cancel', 'inkston_allow_cancel_onhold', 10, 1 );

/*
 * add payment link if order is not paid...
 */
function ii_show_order_payment_link( $order ) {
	if ( ! $order->is_paid() && ! $order->has_status( 'refunded' ) ) {
		$payMethod	 = $order->get_payment_method();
		$isBacs		 = ($payMethod == 'bacs');
		if ( $isBacs ) {
			echo('<p class="saleflash">');
			_e( 'We have not confirmed receipt of payment for this order - ', 'inkston-integration' );
			_e( 'This order is currently set to be paid by bank transfer: if you have already paid then please let us know, otherwise you can make the transfer using the details below or ', 'inkston-integration' );
			echo('<a href="' . esc_url( $order->get_checkout_payment_url() ) . '">');
			_e( 'click here to pay for this order online now.', 'inkston-integration' );
			echo ('</a></p>');
			$available_payment_methods	 = WC()->payment_gateways->get_available_payment_gateways();
			$gatewayBacs				 = $available_payment_methods[ 'bacs' ];
			$gatewayBacs->thankyou_page( $order->get_id() );
		} else {
			echo('<p class="saleflash">');
			_e( 'Payment has not been received for this order - ', 'inkston-integration' );
			echo('<a href="' . esc_url( $order->get_checkout_payment_url() ) . '">');
			_e( 'click here to pay for this order online now.', 'inkston-integration' );
			echo ('</a></p>');
		}
	}
}

/*
 * add payment button if order is not paid...
 * ... it's crazy that you have to exit the order details screen and go back to the order list to be able to pay...
 */
function ii_show_pay_button( $order ) {
	if ( ! $order->is_paid() && ! $order->has_status( 'refunded' ) ) {
		echo ('<a href="' . esc_url( $order->get_checkout_payment_url() ) . '" class="woocommerce-button button pay">' .
		esc_html( __( 'Pay', 'woocommerce' ) ) . '</a>');
	}
}

add_action( 'woocommerce_order_details_before_order_table', 'ii_show_order_payment_link', 10, 1 );
add_action( 'woocommerce_order_details_after_order_table', 'ii_show_pay_button', 10, 1 );

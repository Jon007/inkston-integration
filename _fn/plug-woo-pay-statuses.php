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

add_filter( 'woocommerce_valid_order_statuses_for_payment', 'inkston_allow_pay_onhold', 10, 2 );


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
	if ( ! $order->is_paid() ) {
		echo('<p class="saleflash">');
		_e( 'Payment has not been received for this order - ', 'inkston-integration' );
		echo('<a href="' . esc_url( $order->get_checkout_payment_url() ) . '">');
		_e( 'click here to pay for this order online now.', 'inkston-integration' );
		echo ('</a></p>');
	}
}

/*
 * add payment button if order is not paid...
 * ... it's crazy that you have to exit the order details screen and go back to the order list to be able to pay...
 */
function ii_show_pay_button( $order ) {
	echo ('<a href="' . esc_url( $order->get_checkout_payment_url() ) . '" class="woocommerce-button button pay">' .
	esc_html( __( 'Pay', 'woocommerce' ) ) . '</a>');
}

add_action( 'woocommerce_order_details_before_order_table', 'ii_show_order_payment_link', 10, 1 );
add_action( 'woocommerce_order_details_after_order_table', 'ii_show_pay_button', 10, 1 );

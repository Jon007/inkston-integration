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

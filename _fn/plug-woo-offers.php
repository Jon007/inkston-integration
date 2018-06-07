<?php

/*
 * Special offer messages
 * TODO: set configurable parameters for free shipping level and encouragement level, and disable messages when blank/zero
 */


/*
 * return special offer amount for free shipping
 */
function inkston_free_shipping_level() {
    $level = apply_filters( 'raw_woocommerce_price', 150 );
    if ( isWoocs() ) {
	global $WOOCS;
	$level = $WOOCS->woocs_exchange_value( $level );
    }
    return $level;
}

/*
 * level at which to encourage adding more items for free shipping
 */
function inkston_free_shipping_encourage_level() {
    $level = apply_filters( 'raw_woocommerce_price', 100 );
    if ( isWoocs() ) {
	global $WOOCS;
	$level = $WOOCS->woocs_exchange_value( $level );
    }
    return $level;
}

/*
 * Calculate free shipping message based on current cart amount and any value added
 *
 * @param decimal $valueadd
 *
 * @return string formatted html message '... has been added..  continue shopping'
 */
function inkston_get_cart_message( $valueadd ) {
    //cart and barrier levels translated into current currency
    $encouragement_level	 = inkston_free_shipping_encourage_level();
    $free_level		 = inkston_free_shipping_level();
    $carttotal		 = WC()->cart->cart_contents_total;

    $shippingnote = '';
    if ( $carttotal > $free_level ) {
	//if new items have just pushed total into free shipping eligibility
	if ( ($carttotal - $valueadd) < $free_level ) {
	    $shippingnote = __( 'Congratulations, your order is now eligible for free shipping!', 'inkston-integration' );
	} else {
	    $shippingnote = __( 'Your order qualifies for free shipping!', 'inkston-integration' );
	}
    } elseif ( $carttotal > $encouragement_level ) {
	$shortfall	 = $free_level - $carttotal;
	$shortfall	 = wc_price( $shortfall );
	$shippingnote	 = sprintf( __( 'Add %s more to your order to qualify for free shipping!', 'inkston-integration' ), $shortfall );
    }
    return $shippingnote;
}

/*
 * show remaining amount necessary to qualify for free shipping
 */
function inkston_show_free_shipping_qualifier() {
    $shippingnote = inkston_get_cart_message( 0 );
    if ( $shippingnote ) {
	echo( '<span class="shipping-note">' . $shippingnote . '</span>');
    }
}

add_action( 'woocommerce_after_shipping_calculator', 'inkston_show_free_shipping_qualifier', 10, 0 );

/*
 * Check and add to flash message which appears after adding item to basket
 *
 * @param string $message   formatted html message '... has been added..  continue shopping'
 * @param array $products   array of product ids and quantities just added to basket
 */
function inkston_cart_free_shipping_qualifier( $message, $products ) {
    //get value just added
    $valueadd = 0;
    foreach ( $products as $product_id => $qty ) {
	$product	 = wc_get_product( $product_id );
	$valueadd	 += ($product->get_price() * $qty);
    }
    $carttotal	 = WC()->cart->cart_contents_total;
    $shippingnote	 = inkston_get_cart_message( $valueadd );

    if ( $shippingnote ) {
	$message .= '&#010;<br/>' . $shippingnote;
    }
    return $message;
}

add_filter( 'wc_add_to_cart_message_html', 'inkston_cart_free_shipping_qualifier', 10, 2 );

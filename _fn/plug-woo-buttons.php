<?php

/*
 * Change text of add to cart button
 * add extra checkout button to cart success message
 */


/*
 * adds a checkout button to the product-added-to-cart message
 * which otherwise only shows a continue shopping button
 */
function ink_add_checkout_message( $message, $products ) {
    $checkouturl	 = esc_url( wc_get_page_permalink( 'checkout' ) );
    $checkoutlabel	 = esc_html__( 'Checkout', 'woocommerce' );
    $checkoutbutton	 = sprintf( ' &nbsp; <a href="%s" class="button wc-forward"> &nbsp; %s  &nbsp; </a>', $checkouturl, $checkoutlabel );
//        esc_url( wc_get_page_permalink( 'checkout' ) ),
//        esc_html__( 'Checkout', 'woocommerce' )

    return $checkoutbutton . $message;
}

add_filter( 'wc_add_to_cart_message_html', 'ink_add_checkout_message', 10, 2 );



/* customize cart buttons on archive screens _template_loop_add_to_cart */
function custom_woocommerce_product_add_to_cart_text( $text, $product ) {
    //global $product;
    $product_type = $product->get_type();
    switch ( $product_type ) {
	case 'external':
	    return __( 'Buy', 'inkston-integration' );
	    break;
	case 'grouped':
	    return __( 'View', 'inkston-integration' );
	    break;
	case 'simple':
	case 'woosb':
	    if ( $product->is_in_stock() ) {
		return __( 'Add', 'inkston-integration' );
	    } else {
		return __( 'Read', 'inkston-integration' );
	    }
	    break;
	case 'variable':
	    if ( $product->is_in_stock() ) {
		return __( 'Choose', 'inkston-integration' );
	    } else {
		return __( 'Read', 'inkston-integration' );
	    }
	    break;
	default:
	    return __( 'Read', 'inkston-integration' );
    }
}

add_filter( 'woocommerce_product_add_to_cart_text', 'custom_woocommerce_product_add_to_cart_text', 10, 2 );


<?php

/*
 * inkston custom cart handling
 *  - standard minicart normally removed
 *  - inkston cart content indicators added
 */

/*
 * Ensure cart contents update when products are added to the cart
 * called via AJAX on standard pages even if ajax add to cart is not used
 * also attempts to suppress button refresh on cart page in favour of checkout button
 */
function woocommerce_header_add_to_cart_fragment( $fragments ) {
	if ( isset( $_SERVER[ 'HTTP_REFERER' ] ) ) {
		$url = $_SERVER[ 'HTTP_REFERER' ];
		if ( strpos( $url, '/cart/' ) || strpos( $url, 'es/cesta' ) || strpos( $url, 'fr/panier' ) ) {
			return;
		}
	}

	//woocommerce 3 used to append the wc ajax request to the current url so we could tell
	//which page is being requested.  This is no longer the case!
	//if ( ! is_cart() && ( ! is_checkout()) ) {
	$fragments[ 'span.cart-content' ] = inkston_cart_fragment();
	return $fragments;
}

add_filter( 'woocommerce_add_to_cart_fragments', 'woocommerce_header_add_to_cart_fragment' );
/**
 * Inkston custom cart button contents
 * Uses currency switcher but doesn't depend on its existence
 */
function inkston_cart_fragment() {
	$woocommerce_items_in_cart	 = $cart_value					 = 0;
	$cart_ccy					 = $woocs_current_currency		 = 'USD';
	$display_value				 = $result						 = '';

	//err on the side of never showing cart when woocommerce hasn't set the cart cookie
    //note that woocommerce_items_in_cart is binary, not the actual number of items
	if ( isset( $_COOKIE[ 'woocommerce_cart_hash' ] ) &&
	isset( $_COOKIE[ 'woocommerce_items_in_cart' ] ) &&
	1 == $_COOKIE[ 'woocommerce_items_in_cart' ] ) {
		if ( isset( $_COOKIE[ 'wc_items' ] ) ) {
			$woocommerce_items_in_cart = $_COOKIE[ 'wc_items' ];
		}
	}
	if ( $woocommerce_items_in_cart ) {
		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( "DONOTCACHEPAGE", true );
		}
		if ( isset( $_COOKIE[ 'wc_ccy' ] ) ) {
			$cart_ccy = $_COOKIE[ 'wc_ccy' ];
		}
		if ( isset( $_COOKIE[ 'woocs_current_currency' ] ) ) {
			$woocs_current_currency = $_COOKIE[ 'woocs_current_currency' ];
		}
		if ( isset( $_COOKIE[ 'wc_val' ] ) ) {
			$cart_value = $_COOKIE[ 'wc_val' ];
		}
        /*
        //Note: wc_disp could contains already formatted amount depending on version of ccy switcher and woo
		if ( isset( $_COOKIE[ 'wc_disp' ] ) ) {
			$display_value = stripslashes( $_COOKIE[ 'wc_disp' ] );
            //php Bug #79174 spaces dont round trip properly
            $display_value = str_replace('+', ' ', $display_value);
		} else {
         * 
         */
		    $display_value = $cart_value;
        //}
		//if cart not USD, convert into USD to get base amount for any other conversions
		//if ( $woocs_current_currency != $cart_ccy ) {
		if ( $cart_ccy != 'USD' || $woocs_current_currency != $cart_ccy ) {
			//only call into currency switcher and woocommmerce if we have actually switched currency
			global $WOOCS;
			if ( $WOOCS ) {
				if ( $woocs_current_currency != $cart_ccy ) {
					$rate = $WOOCS->get_currencies()[ $woocs_current_currency ][ 'rate' ] / $WOOCS->get_currencies()[ $cart_ccy ][ 'rate' ];
					if ( $rate ) {
						$display_value	 = $cart_value * $rate;
					}
				}
				//base value must always be USD
				if ( $cart_ccy != 'USD' ) {
					$rate = $WOOCS->get_currencies()[ $cart_ccy ][ 'rate' ];
					if ( $rate ) {
						$cart_value = round($cart_value / $rate, 2, PHP_ROUND_HALF_UP);
					}
				}
			}
		}
    $display_value	 = wc_price( $display_value );
		$result = '<span data-price="' . $cart_value . '" class="woocs_price_code cart-content">' .
		'<span class="cart-total">' . $woocommerce_items_in_cart . '</span>' .
		'<span class="woocommerce-Price-amount amount">' . $display_value . '</span></span>';
	} else { //empty cart result
		$result = '<span class="cart-content"> </span>';
	}
	return $result;
}

/*
 * generate cart button link wrapping contents and linking to shopping cart
 */
function inkston_cart_link( $wrapper_class = 'header-cart' ) {
	$button_url		 = $button_title	 = '';
	$button_text	 = inkston_cart_fragment();
	if ( class_exists( 'woocommerce' ) ) {

		if ( (is_cart()) && (sizeof( WC()->cart->cart_contents ) > 0) ) {
			$button_url		 = esc_url( wc_get_checkout_url() );
			$button_title	 = __( 'Checkout', 'inkston-integration' );
			$button_text	 = '<span class="cart-content">' . $button_title . '</span>';
		} elseif ( is_checkout() ) {
			$button_url	 = esc_url( wc_get_cart_url() );
			$button_text = '<span class="cart-content"> </span>';
		} else {
			$button_url = esc_url( wc_get_cart_url() );
		}
	} else {
		$button_url = ink_cart_url();
	}

	if ( $button_url ) {
		echo ( '<ul class="header-cart">');
		echo ( '<li class="menu-item"><a class="button alt" href="' . $button_url . '" title="' . $button_title . '">');
		echo ($button_text);
		echo( '</a></li></ul>');
	}
	if ( function_exists( 'output_ccy_switcher_button' ) ) {
		output_ccy_switcher_button();
	}
}

<?php

/*
 * Special offer messages
 * TODO: set configurable parameters for free shipping level and encouragement level, and disable messages when blank/zero
 */


/*
 * return special offer amount for free shipping
 */
function inkston_free_shipping_level() {
	$ii_options	 = ii_get_options();
	$level		 = ( isset( $ii_options[ 'woofreeshippinglevel' ] )) ? $ii_options[ 'woofreeshippinglevel' ] : 150;
	if ( is_numeric( $level ) ) {
		//raw price is not filtered by currency switcher
		$level = apply_filters( 'raw_woocommerce_price', $level );
    if ( isWoocs() ) {
      global $WOOCS;
      $level = $WOOCS->woocs_exchange_value( $level );
    }
	}
  return $level;
}

/*
 * level at which to encourage adding more items for free shipping
 */
function inkston_free_shipping_encourage_level() {
	$ii_options	 = ii_get_options();
	$level		 = ( isset( $ii_options[ 'woofreeshippingencourage' ] )) ? $ii_options[ 'woofreeshippingencourage' ] : 150;
	$level		 = apply_filters( 'raw_woocommerce_price', $level );
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
	$knownFreeRegion	 = false;

	//if we already know customer is not in location eligible for free shipping,
	//suppress free shipping messages
	//(if customer is in known free shipping zone, update free & encourage levels)
	$packages = WC()->cart->get_shipping_packages();
	if ( $packages ) {
		$package = array_shift( $packages );
		if ( isset( $package[ 'destination' ] ) && isset( $package[ 'destination' ][ 'country' ] ) && ( $package[ 'destination' ][ 'country' ] != '') ) {
			$shipping_zone = WC_Shipping_Zones::get_zone_matching_package( $package );
			if ( $shipping_zone ) {
				$shipping_methods = $shipping_zone->get_shipping_methods( true );
				if ( $shipping_methods ) {
					$couldBeFree = false;
					foreach ( $shipping_methods as $shipping_method ) {
						if ( get_class( $shipping_method ) == 'WC_Shipping_Free_Shipping' ) {
							$couldBeFree			 = true;
							$knownFreeRegion		 = true;
							$shippingMethodFreeLevel = $shipping_method->min_amount;
							if ( is_numeric( $shippingMethodFreeLevel ) ) {
								if ( isWoocs() ) {
									global $WOOCS;
									$shippingMethodFreeLevel = $WOOCS->woocs_exchange_value( $shippingMethodFreeLevel );
								}
								//if we have a free shipping level for zone, use that rather than global setting
								$free_level			 = $shippingMethodFreeLevel;
								$encouragement_level = ( $free_level * 0.8 );
							}
						}
					}
					if ( ! $couldBeFree ) {
						return '';
					}
				}
			}
		}
	}


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
	$ii_options = ii_get_options();
	//if we are not in a known free shipping area, add exception message..
	if ( $shippingnote && ( ! $knownFreeRegion) && isset( $ii_options[ 'woofreeshippingexcept' ] ) && $ii_options[ 'woofreeshippingexcept' ] != '' ) {
		$freeshippingexceptions = $ii_options[ 'woofreeshippingexcept' ];
		if ( function_exists( 'pll__' ) ) {
			$freeshippingexceptions = pll__( $freeshippingexceptions );
		}
		$shippingnote .= ' ' . $freeshippingexceptions;
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

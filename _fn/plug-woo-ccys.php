<?php

/*
 * currency switcher customizations
 */

/* add currency switcher in logincal place on product screen */
add_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 6 );
add_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 7 );
add_action( 'woocommerce_single_product_summary', 'output_ccy_switcher', 8 );
remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 10 );
remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );

/* Add currency switcher to cart and checkout screens */
add_action( 'woocommerce_cart_totals_after_order_total', 'output_ccy_switcher', 10 );
add_action( 'woocommerce_checkout_order_review', 'output_ccy_switcher', 10 );

/* localise Currency switcher initialization parameters */
add_filter( 'woocs_currency_description', 'localize_currency_description', 10, 2 );
add_filter( 'woocs_currency_data_manipulation', 'localize_currency_switcher', 10, 1 );

/* free shipping eligibility take into account currency */
add_filter( 'woocommerce_shipping_free_shipping_is_available', 'free_shipping_is_available', 99, 3 );


/*
 * Localise initialization parameters for WooCommerce Currency Switcher, if installed:
 *
 * 	    'USD' => array(
 * 		'name' => 'USD',
 * 		'rate' => 1,
 * 		'symbol' => '&#36;',
 * 		'position' => 'right',
 * 		'is_etalon' => 1,
 * 		'description' => 'USA dollar',
 * 		'hide_cents' => 0,
 * 		'flag' => '',
 */
function localize_currency_switcher( $currencies ) {
	//allow a per-language transient cache of currency info
	$locale			 = (function_exists( 'pll_current_language' )) ? pll_current_language( 'locale' ) : get_locale();
	$tKey			 = 'currencies-' . $locale;
	$transcurrencies = get_transient( $tKey );
	if ( $transcurrencies ) {
		return $transcurrencies;
	}

    if ( ! isset( $currencies[ 'GBP' ] ) ) {
	$currencies[ 'GBP' ] = array(
	    'name'		 => 'GBP',
	    'rate'		 => 0.78,
	    'symbol'	 => '&#163;',
	    'position'	 => 'left',
	    'is_etalon'	 => 0,
	    'description'	 => 'UK Pound Sterling (GBP)',
	    'hide_cents'	 => 0,
	    'flag'		 => '',
	    'decimals'	 => 2,
	);
    }
    if ( ! isset( $currencies[ 'AUD' ] ) ) {
	$currencies[ 'AUD' ] = array(
	    'name'		 => 'AUD',
	    'rate'		 => 1.31,
	    'symbol'	 => 'A&#36;',
	    'position'	 => 'left',
	    'is_etalon'	 => 0,
	    'description'	 => 'Australian Dollar (AUD)',
	    'hide_cents'	 => 0,
	    'flag'		 => '',
	    'decimals'	 => 2,
	);
    }
    /*
      if (! isset($currencies['CAD']) )
      {
      $currencies['CAD'] = array(
      'name' => 'CAD',
      'rate' => 1.32,
      'symbol' => 'C&#36;',
      'position' => 'left',
      'is_etalon' => 0,
      'description' => 'Canadian Dollar (CAD)',
      'hide_cents' => 0,
      'flag' => '',
      'decimals' => 2,
      );
      }
     */

    $woo_localized_descriptions = get_woocommerce_currencies();
    foreach ( $currencies as $currency ) {
	$code = $currency[ 'name' ];

	//use preset description
	$description = $woo_localized_descriptions[ $code ];
	if ( $description ) {
	    $currencies[ $code ][ 'description' ] = $description . ' ( ' . $code . ')';
	}

	//localize position and hide_cents where possible
	//added this because WP-CLI threw a wobbly over the NumberFormatter and
	//refused to perfom any operations, even completely unrelated to theme..
	$formatter = NULL;
	try {
	    if ( class_exists( '\NumberFormatter' ) ) {
		$formatter = new \NumberFormatter( $locale . '@currency=' . $code, \NumberFormatter::CURRENCY );
	    }
	} catch ( Exception $e ) {

	}
	if ( $formatter ) {
	    $symbol = $formatter->getTextAttribute( \NumberFormatter::CURRENCY_SYMBOL );
	    if ( $symbol ) {
		$currencies[ $code ][ 'symbol' ] = $symbol;
	    }

	    $prefix					 = $formatter->getTextAttribute( \NumberFormatter::POSITIVE_PREFIX );
	    $currencies[ $code ][ 'position' ]	 = (strlen( $prefix )) ? 'left' : 'right';

	    $decimals				 = $formatter->getAttribute( \NumberFormatter::FRACTION_DIGITS );
	    $currencies[ $code ][ 'hide_cents' ]	 = ($decimals) ? false : true;
	}
    }

	set_transient( $tKey, $currencies, 3600 );
    return $currencies;
}

/*
 * returns description from woocommerce supplied currency array
 */
function localize_currency_description( $description, $currency ) {
    $retval		 = $description;
    $currencies	 = get_woocommerce_currencies();
    if ( isset( $currencies[ $currency ] ) ) {
	$retval = $currencies[ $currency ];
    }
    return $retval;
}

function output_ccy_switcher() {
    echo do_shortcode( "[woocs width='300px' txt_type='desc']" );
}

function output_ccy_switcher_button() {
    //always output button due to caching, hide via css if not needed..
    //if ( isWoocs() ) { // && ( (is_shop() ) || (sizeof(WC()->cart->cart_contents) > 0) ) ){
    $wrapper_class	 = 'header-cart ccy';
    $button_class	 = 'menu-item';
    echo ( '<ul class="' . $wrapper_class . '">');
    echo ( '<li class="' . $button_class . '">');
    echo do_shortcode( '[woocs]' );
    echo( '</li></ul>');
    //}
}

/**
 * See if free shipping is available based on the package and cart.
 * Updated multi-currency adaptation:  free shipping threshold is in USD
 *
 * @param bool $is_available calculated as available by wooCommerce
 * @param array $package Shipping package.
 * @param WC_Shipping_Free_Shipping $free_Shipping_Method
 * @return bool
 */
function free_shipping_is_available( $is_available, $package, $free_Shipping_Method ) {
	$has_coupon			 = false;
	$has_met_min_amount	 = false;

	if ( in_array( $free_Shipping_Method->requires, array( 'coupon', 'either', 'both' ), true ) ) {
		$coupons = WC()->cart->get_coupons();

		if ( $coupons ) {
			foreach ( $coupons as $code => $coupon ) {
				if ( $coupon->is_valid() && $coupon->get_free_shipping() ) {
					$has_coupon = true;
					break;
				}
			}
		}
	}

	if ( in_array( $free_Shipping_Method->requires, array( 'min_amount', 'either', 'both' ), true ) ) {
		$total = WC()->cart->get_displayed_subtotal();

		if ( WC()->cart->display_prices_including_tax() ) {
			$total = round( $total - ( WC()->cart->get_discount_total() + WC()->cart->get_discount_tax() ), wc_get_price_decimals() );
		} else {
			$total = round( $total - WC()->cart->get_discount_total(), wc_get_price_decimals() );
		}

		$minAmount = $free_Shipping_Method->min_amount;
		if ( $minAmount ) {
			if ( isWoocs() ) {
				global $WOOCS;
				$minAmount = $WOOCS->woocs_exchange_value( $minAmount );
			}
		}
		if ( $total >= $minAmount ) {
			$has_met_min_amount = true;
		}
		/* don't consider global free shipping level as filtering by region
		  $inkMinAmount = inkston_free_shipping_level();
		  if ( $total >= $inkMinAmount ) {
		  $has_met_min_amount = true;
		  }
		 */
	}

	switch ( $free_Shipping_Method->requires ) {
		case 'min_amount':
			$is_available	 = $has_met_min_amount;
			break;
		case 'coupon':
			$is_available	 = $has_coupon;
			break;
		case 'both':
			$is_available	 = $has_met_min_amount && $has_coupon;
			break;
		case 'either':
			$is_available	 = $has_met_min_amount || $has_coupon;
			break;
		default:
			$is_available	 = true;
			break;
	}

	return $is_available;
}

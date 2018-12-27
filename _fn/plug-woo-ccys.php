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

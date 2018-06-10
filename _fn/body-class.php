<?php

/*
 * body classes provide a powerful way of customizing display
 *  - fullpage changes layout
 *  - psgal class turns on photoswipe
 *  -
 */
/**
 * Add body class
 */
function inkston_body_class_filter( $classes ) {

	/* psgal class enables photoswipe gallery */
	//if ((is_single()) || is_product_category()  || is_category() )
	$classes[] = sanitize_html_class( 'psgal' );

	if ( is_page_template( 'template-fullpage.php' ) ) {
		$classes[] = sanitize_html_class( 'fullpage' );
	}
	if ( is_page_template( 'template-posttiles.php' ) ) {
		$classes[] = sanitize_html_class( 'fullpage' );
	}
	if ( is_page_template( 'template-posttiles2.php' ) ) {
		$classes[] = sanitize_html_class( 'fullpage' );
	}
	if ( is_page_template( 'template-posttiles3.php' ) ) {
		$classes[] = sanitize_html_class( 'fullpage' );
	}
//    if (is_single()){
	//always need a woocommerce in there for formatting related products
//    }

	if ( ! is_page() && ! is_single() && ! is_search() )
		$classes[] = sanitize_html_class( 'colgrid' );

	if ( class_exists( 'woocommerce' ) ) {
		$classes[]	 = (sizeof( WC()->cart->cart_contents ) == 0) ? 'cart-empty' : 'cart-show';
		$classes[]	 = 'woocommerce-page';
		$woo_cols	 = get_option( 'woocommerce_catalog_columns', 5 );
		$classes[]	 = 'columns-' . $woo_cols;
		$classes[]	 = sanitize_html_class( 'woocommerce' );

		//hack: treat checkout as home and remove viewport to fix scrolling issue
		if ( is_checkout() ) {
			$classes[] = 'home';
		}
	}

	$cookie_name = 'pll_language';
	if ( isset( $_COOKIE[ $cookie_name ] ) && ( $_COOKIE[ $cookie_name ]) ) {
		$classes[]	 = 'lang';
		$classes[]	 = $_COOKIE[ $cookie_name ];
	} else { /* default to english if no language set yet */
		$classes[]	 = 'lang';
		$classes[]	 = 'en';
	}

	if ( isset( $_GET[ 'mailpoet_router' ] ) ) {
		$classes[] = 'mailpoet_router';
	}

	return $classes;
}

add_filter( 'body_class', 'inkston_body_class_filter' );

/*
 * enqueue css if this is enabled
 */
inkston_integration::get_instance()->ii_enqueue_script( 'ii-body' );

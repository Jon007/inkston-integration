<?php

/*
 * general woocommerce customizations plus
 * customisations for other woocommerce extensions if these are loaded
 */
if ( isset( $ii_options[ 'amazonusa' ] ) || isset( $ii_options[ 'amazoneu' ] ) ) {
	include_once( 'plug-woo-amazon.php' );
}
if ( isset( $ii_options[ 'asinupc' ] ) ) {
	include_once( 'plug-woo-asin-upc-net.php' );
}
if ( isset( $ii_options[ 'buttons' ] ) ) {
	include_once( 'plug-woo-buttons.php' );
}
if ( isset( $ii_options[ 'cart' ] ) ) {
	include_once( 'plug-woo-cart.php' );
}
if ( isset( $ii_options[ 'woocoupons' ] ) && defined( 'WJECF_VERSION' ) ) {
	include_once( 'plug-woo-coupons.php' );
}
function isWoocs() {
	global $WOOCS;
	return ($WOOCS) ? true : false;
}

if ( isWoocs() && isset( $ii_options[ 'ccys' ] ) ) {
	include_once( 'plug-woo-ccys.php' );
}
if ( class_exists( 'Alg_WC_Checkout_Files_Upload_Main' ) && isset( $ii_options[ 'files' ] ) ) {
	include_once( 'plug-woo-checkout-files.php' );
}
include_once( 'plug-woo-customizer.php' );
if ( isset( $ii_options[ 'loginredir' ] ) ) {
	include_once( 'plug-woo-login.php' );
}
if ( isset( $ii_options[ 'woofreeshippingoffer' ] ) ) {
  include_once( 'plug-woo-offers.php' );
}
if ( isset( $ii_options[ 'paystatus' ] ) ) {
	include_once( 'plug-woo-pay-statuses.php' );
}
if ( class_exists( 'Polylang' ) ) {
	include_once( 'plug-woo-poly-.php' );
}

if ( class_exists( 'WPcleverWoosb' ) && isset( $ii_options[ 'bundle' ] ) ) {
	include_once( 'plug-woo-product-bundles.php' );
}

if ( class_exists( 'Woo_Sell_Coupons' ) && isset( $ii_options[ 'vouchers' ] ) ) {
	include_once( 'plug-woo-product-gift.php' );
}
if ( isset( $ii_options[ 'group' ] ) ) {
	include_once( 'plug-woo-product-grouped.php' );
}
if ( defined( 'WPSEO_VERSION' ) && isset( $ii_options[ 'wooseo' ] ) ) {
	include_once( 'plug-woo-seo.php' );
}
if ( class_exists( 'WC_Stripe' ) && isset( $ii_options[ 'stripe' ] ) ) {
	include_once( 'plug-woo-stripe.php' );
}
if ( isset( $ii_options[ 'hovercat' ] ) ) {
	include_once( 'plug-woo-tooltips.php' );
}

if ( class_exists( 'TInvWL_Activator' ) ) {
	include_once( 'plug-woo-wishlist.php' );
}

if ( isset( $ii_options[ 'sku' ] ) ) {
	include_once( 'plug-woo-sku.php' );
}

if ( isset( $ii_options[ 'wootemplates' ] ) ) {
	include_once( 'plug-woo-templates.php' );
}

if ( isset( $ii_options[ 'allowbackorders' ] ) ) {
	include_once( 'plug-woo-backorders.php' );
}

include_once( 'plug-woo-variations.php' );


// Ship to a different address closed by default
add_filter( 'woocommerce_ship_to_different_address_checked', '__return_false' );

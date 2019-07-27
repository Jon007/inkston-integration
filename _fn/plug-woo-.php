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
if ( isset( $ii_options[ 'woopdf' ] ) ) {
	include_once( 'plug-woo-pdf.php' );
}

if ( isset( $ii_options[ 'wootemplates' ] ) ) {
	include_once( 'plug-woo-templates.php' );
}

if ( isset( $ii_options[ 'allowbackorders' ] ) ) {
	include_once( 'plug-woo-backorders.php' );
}

include_once( 'plug-woo-variations.php' );

include_once( 'plug-woo-emails.php' );

// Ship to a different address closed by default
add_filter( 'woocommerce_ship_to_different_address_checked', '__return_false' );

/*
 * include account name in the payment details information as this has to be exactly correct for chinese banks
 */
function ii_bacs_add_account_name( $account_fields ) {
	$available_payment_methods	 = WC()->payment_gateways->get_available_payment_gateways();
	$gatewayBacs				 = $available_payment_methods[ 'bacs' ];
	$bacs_accounts				 = $gatewayBacs->account_details;
	if ( ! empty( $bacs_accounts ) ) {
		$accountNumber	 = $account_fields[ 'account_number' ][ 'value' ];
		$bankName		 = $account_fields[ 'bank_name' ][ 'value' ];
		foreach ( $bacs_accounts as $bacs_account ) {
			$bacsBankName		 = $bacs_account[ 'bank_name' ];
			$bacsAccountNumber	 = $bacs_account[ 'account_number' ];
			if ( $bacsBankName == $bankName && $bacsAccountNumber == $accountNumber ) {
	$account_fields[ 'account_name' ] = array(
		'label'	 => __( 'Account name', 'woocommerce' ),
					'value'	 => wp_kses_post( wp_unslash( $bacs_account[ 'account_name' ] ) ),
	);
				break;
			}
		}
	}
	$order_id	 = (isset( $GLOBALS[ 'view-order' ] )) ? $GLOBALS[ 'view-order' ] : WC()->order_factory->get_order_id( false );
	if ( $order_id ) {
		$account_fields[ 'payment_ref' ] = array(
			'label'	 => __( 'Payment reference', 'inkston-integration' ),
			'value'	 => 'INK' . $order_id,
		);
	}
	return $account_fields;
}

add_filter( 'woocommerce_bacs_account_fields', 'ii_bacs_add_account_name' );

/*
 * woocommerce integration for BanHammer spam control from
 * https://github.com/Ipstenu/ban-hammer/wiki#woocommerce
 */
add_filter( 'woocommerce_registration_errors', 'woocommerce_banhammer_validation', 10, 3 );
function woocommerce_banhammer_validation( $validation_errors, $username, $email ) {
	//use condition to skip checks on checkout - is_account_page() does not work
	//because account is actually created on ajax
	if ( ! is_ajax() ) {
		if ( class_exists( 'BanHammer' ) ) {
			if ( (new BanHammer )->banhammer_drop( $username, $email, $validation_errors ) ) {
				error_log( sprintf(
				__( 'Registration email %1$s for user %1$s blocked by BanHammer rule', 'inkston-integration' ), $email, $username ) );
				return new WP_Error( 'registration-error-bad-email', (new BanHammer)->options[ 'message' ] );
			}
		}
		if ( isset( $_REQUEST[ 'password2' ] ) ) {
			if ( $_REQUEST[ 'password2' ] != 'oldpassword' ) {
				error_log( sprintf(
				__( 'Registration email %1$s for user %1$s blocked due to password2 honeypot', 'inkston-integration' ), $email, $username ) );
				return new WP_Error( 'registration-error-bad-email', (new BanHammer)->options[ 'message' ] );
			}
		} else {
			error_log( sprintf(
			__( 'Registration email %1$s for user %1$s blocked due to missing password2 ', 'inkston-integration' ), $email, $username ) );
			return new WP_Error( 'registration-error-bad-email', (new BanHammer)->options[ 'message' ] );
		}
		if ( isset( $_REQUEST[ 'password' ] ) ) {
			if ( $_REQUEST[ 'password' ] == '' ) {
				return new WP_Error( 'registration-error-blank-password', __( 'Please enter a password!', 'inkston-integration' ) );
			}
		}
	} else {
		error_log( sprintf(
		__( 'Registration email %1$s for user %1$s accepted without further checks as registration is in ajax (not a registration page submission)', 'inkston-integration' ), $email, $username ) );
	}
	return $validation_errors;
}

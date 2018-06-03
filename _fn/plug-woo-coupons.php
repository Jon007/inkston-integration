<?php

/*
 * overrides standard coupon html to display description instead of 0.00 if coupon
 * has no value and is not a free shipping coupon
 *  - applies normally to free product coupons
 */
function ii_coupon_html( $coupon_html, $coupon, $discount_amount_html ) {

	$amount = WC()->cart->get_coupon_discount_amount( $coupon->get_code(), WC()->cart->display_cart_ex_tax );
	if ( $amount || $coupon->get_free_shipping() ) {
		return $coupon_html;
	} else {
		$coupon_excerpt = $coupon->get_description();
		if ( $coupon_excerpt ) {
			return $coupon_excerpt . ' <a href="' . esc_url( add_query_arg( 'remove_coupon', rawurlencode( $coupon->get_code() ), defined( 'WOOCOMMERCE_CHECKOUT' ) ? wc_get_checkout_url() : wc_get_cart_url() ) ) . '" class="woocommerce-remove-coupon" data-coupon="' . esc_attr( $coupon->get_code() ) . '">' . __( '[Remove]', 'woocommerce' ) . '</a>';
		} else {
			return $coupon_html;
		}
	}
}

add_filter( 'woocommerce_cart_totals_coupon_html', 'ii_coupon_html', 20, 3 );

<?php

/*
 * universally allow back orders
 * exception, if explicitly set to out of stock, products can be hidden from catalog and not orderable
 * but by default should be treated as back-orderable once out of stock
 */
function ink_backorders_allowed( $allowed, $productid, $product ) {
	return true;
}

add_filter( 'woocommerce_product_backorders_allowed', 'ink_backorders_allowed', 10, 3 );



//apply_filters( $this->get_hook_prefix() . $prop, $value, $this );
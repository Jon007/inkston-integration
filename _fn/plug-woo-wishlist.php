<?php

/*
 * woocommerce wishlist customizations
 */

/*
 * change the default name of the wishlist to include user name
 */
function ink_default_wishlist_name( $wl ) {
	if ( array_key_exists( 'author', $wl ) && array_key_exists( 'title', $wl ) ) {
		$user = get_userdata( $wl[ 'author' ] );
		if ( $user && $user->user_nicename ) {
			$wl[ 'title' ] = $user->user_nicename . ' ' . $wl[ 'title' ];
		}
	}
	return $wl;
}

add_filter( 'tinvwl_wishlist_get', 'ink_default_wishlist_name', 10, 1 );

/*
 * remove bundle ids from the wishlist display
 */
function ink_suppress_wishlist_bundle_details( $form ) {
	unset( $form[ 'woosb_ids' ] );
	return $form;
}

add_filter( 'tinvwl_addtowishlist_add_form', 'ink_suppress_wishlist_bundle_details', 10, 1 );

/*
 * enqueue css if this is enabled
 */
inkston_integration::get_instance()->ii_enqueue_script( 'ii-wishlist' );

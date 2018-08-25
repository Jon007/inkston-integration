<?php

/*
 * polylang customisation functions not specific to woocommerce
 */

/*
 * as soon as polylang detects language, set a cache key salt
 * to avoid unwanted redis cache issues
 * actually this code is run on init after polylang already chose language
 * so the action is already called before hooking it here, so set the salt directly
  function ink_lang_cache_key_salt( $slug, $lang ) {
  if ( ! defined( 'WP_CACHE_KEY_SALT' ) ) {
  define( 'WP_CACHE_KEY_SALT', get_home_url( 1, '', 'relative' ) . $slug );
  }
  }
  add_action( 'pll_language_defined', 'ink_lang_cache_key_salt', 10, 2 );
 */
if ( ! defined( 'WP_CACHE_KEY_SALT' ) ) {
	define( 'WP_CACHE_KEY_SALT', $_SERVER[ 'HTTP_HOST' ] . '/' . pll_current_language() );
}


/*
 *  Make sure Polylang copies the title when creating a translation
 */
function hy_editor_title( $title ) {
    // Polylang sets the 'from_post' parameter
    if ( isset( $_GET[ 'from_post' ] ) ) {
	$my_post = get_post( $_GET[ 'from_post' ] );
	if ( $my_post )
	    return $my_post->post_title;
    }
    return $title;
}

add_filter( 'default_title', 'hy_editor_title' );

/*
 *  Make sure Polylang copies the content when creating a translation
 */
function hy_editor_content( $content ) {
    // Polylang sets the 'from_post' parameter
    if ( isset( $_GET[ 'from_post' ] ) ) {
	$my_post = get_post( $_GET[ 'from_post' ] );
	if ( $my_post )
	    return $my_post->post_content;
    }
    return $content;
}

add_filter( 'default_content', 'hy_editor_content' );

/*
 *  Make sure Polylang copies the excerpt [woocommerce short description] when creating a translation
 */
function hy_editor_excerpt( $excerpt ) {
    // Polylang sets the 'from_post' parameter
    if ( isset( $_GET[ 'from_post' ] ) ) {
	$my_post = get_post( $_GET[ 'from_post' ] );
	if ( $my_post )
	    return $my_post->post_excerpt;
    }
    return $excerpt;
}

add_filter( 'default_excerpt', 'hy_editor_excerpt' );


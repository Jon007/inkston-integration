<?php

/*
 * polylang customisation functions not specific to woocommerce
 */

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


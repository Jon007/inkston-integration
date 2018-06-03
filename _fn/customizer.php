<?php

/*
 * These functions belong in the customizer as they affect the display
 */


/*
 * limit eg search results per page
 * TODO: implement: sample code is not correct as posts_per_page should be set
 *	    before calculating the LIMIT clause
 *
 * @param string   $limits The LIMIT clause of the query.
 * @param WP_Query $query   The WP_Query instance (passed by reference).
 */
/*
function postsperpage( $limits, $query ) {
    if ( is_search() ) {
	global $wp_query;
	$wp_query->query_vars[ 'posts_per_page' ] = 12;
    }
    return $limits;
}
add_filter( 'post_limits', 'postsperpage' );
*/



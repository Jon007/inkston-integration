<?php

/*
 * Relevanssi search customization
 */


/* disable shortcodes problematic for relevannsi */
function ink_nosearch_shortcodes( $arr ) {
	$problem_shortcodes	 = '';
	$ii_options			 = ii_get_options();
	if ( isset( $ii_options[ 'relevanssi' ] ) ) {
		$problem_shortcodes = $ii_options[ 'relevanssi' ];
	}
	if ( $problem_shortcodes == '' ) {
		$problem_shortcodes = array( 'inkpoints', 'inklevel', 'badgeos_achievements_list', 'robo-gallery', 'maxmegamenu' );
	}
	if ( is_array( $arr ) ) {
		return array_merge( $arr, $problem_shortcodes );
	} else {
		return $problem_shortcodes;
	}
}

add_filter( 'relevanssi_disable_shortcodes_excerpt', 'ink_nosearch_shortcodes', 10, 1 );
add_filter( 'pre_option_relevanssi_expand_shortcodes', 'ink_nosearch_shortcodes', 10, 1 );




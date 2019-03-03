<?php

/*
 * admin mode customisations
 */

/*
 * always remove robo gallery button from edit area, no longer used
 */
remove_action( 'media_buttons', 'add_robo_gallery_button', 15 );


/*
 * remove popups button from tinymce editor
 */
function ii_mce_button_filter( $buttons ) {
	$key = array_search( 'spu_button', $buttons );
	if ( $key ) {
		unset( $buttons[ $key ] );
	}
	$key = array_search( 'dfw', $buttons );
	if ( $key ) {
		unset( $buttons[ $key ] );
	}
	return $buttons;
}

add_filter( 'mce_buttons', 'ii_mce_button_filter', 99, 1 );

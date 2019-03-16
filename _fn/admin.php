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



/*
 * remove admin menus for non-admin users
 */
if ( ! current_user_can( 'manage_options' ) ) {
	remove_menu_page( 'wpcf7' );
	remove_menu_page( 'edit.php?post_type=robo_gallery_table' );
	remove_menu_page( 'tools.php' );
	remove_menu_page( 'options-general.php' );
	if ( class_exists( 'woocommerce' ) ) {
	remove_submenu_page( 'woocommerce', 'wc-order-export' );
		remove_submenu_page( 'woocommerce', 'wc-reports' );
		remove_submenu_page( 'woocommerce', 'wc-settings' );
		remove_submenu_page( 'woocommerce', 'wc-status' );
		remove_submenu_page( 'woocommerce', 'wc-addons' );
		remove_submenu_page( 'woocommerce', 'wpo_wcpdf_options_page' );
		remove_submenu_page( 'woocommerce', 'alg-custom-order-statuses-tool' );
	}
}

//admin_init is already called when admin.php is executed
//add_action( 'admin_init', 'ii_debug_admin_menu' );

//to find ids of menus to hide, debug menu information
//echo '<div class="admin-debug"><h1>admin menu debug</h1>';
//echo '<pre>' . print_r( $GLOBALS[ 'menu' ], TRUE) . '</pre>';
//echo '</div>';

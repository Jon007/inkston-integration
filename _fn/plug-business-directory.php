<?php

/*
 * customisations for Business Directory Plugin https://wordpress.org/plugins/business-directory-plugin/
 * to support translation for dynamic front-end switching
 */

/*
 * This hack inserts directory field labels into the photoline-inkston text translation
 */
function get_directory_labels() {
	static $tr_directory_label;
	$tr_directory_label[ 'Name' ]			 = __( 'Name', 'inkston-integration' );
	$tr_directory_label[ 'Category' ]		 = __( 'Category', 'inkston-integration' );
	$tr_directory_label[ 'Country' ]		 = __( 'Country', 'inkston-integration' );
	$tr_directory_label[ 'Contact Email' ]	 = __( 'Contact Email', 'inkston-integration' );
	$tr_directory_label[ 'Location' ]		 = __( 'Location', 'inkston-integration' );
	$tr_directory_label[ 'About' ]			 = __( 'About', 'inkston-integration' );
	$tr_directory_label[ 'Summary' ]		 = __( 'Summary', 'inkston-integration' );
	$tr_directory_label[ 'Website' ]		 = __( 'Website', 'inkston-integration' );
	return $tr_directory_label;
}

/*
 * This hack inserts directory field descriptions into the photoline-inkston text translation
 */
function get_directory_descriptions() {
	$tr_directory_description[ 'Artist or studio name' ]																																						 = __( 'Artist or studio name ', 'inkston-integration' );
	$tr_directory_description[ 'Select one or more categories for your listing' ]																																 = __( 'Select one or more categories for your listing', 'inkston-integration' );
	$tr_directory_description[ 'Main country' ]																																									 = __( 'Main country ', 'inkston-integration' );
	$tr_directory_description[ 'Please enter town or region to help visitors find you' ]																														 = __( 'Please enter town or region to help visitors find you ', 'inkston-integration' );
	$tr_directory_description[ 'To avoid spam, Email address will never be shown, instead a contact form will be provided which is only available to genuine logged-on users.' ]								 = __( 'To avoid spam, Email address will never be shown, instead a contact form will be provided which is only available to genuine logged-on users. ', 'inkston-integration' );
	$tr_directory_description[ 'How did you start with Oriental arts? What are your favourite techniques?  Do you sell your artwork, do you accept commissions?  Do you teach or can you recommend teachers?' ]	 = __( 'How did you start with Oriental arts? What are your favourite techniques?  Do you sell your artwork, do you accept commissions?  Do you teach or can you recommend teachers? ', 'inkston-integration' );
	$tr_directory_description[ 'Here you can make a special short summary for search engines and search results.  Leave blank for an automatic summary.' ]														 = __( 'Here you can make a special short summary for search engines and search results.  Leave blank for an automatic summary. ', 'inkston-integration' );
	$tr_directory_description[ 'Main website (can be any link including Facebook page if that is your main page)' ]																								 = __( 'Main website (can be any link including Facebook page if that is your main page) ', 'inkston-integration' );
	$tr_directory_description[ 'add optional tags separated by commas, to allow more classifications than available under categories ' ]																		 = __( 'add optional tags separated by commas, to allow more classifications than available under categories ', 'inkston-integration' );
	return $tr_directory_description;
}

/**
 * filter and translate field labels
 *
 * @param string      $label input value
 *
 * @return string     $label or translation if found
 */
function ink_wpbdp_field_label( $label ) {
	$locale				 = get_locale();
	$tr_directory_label	 = get_directory_labels();
	switch ( $locale ) {
		case 'fr_FR':
		case 'de_DE':
		case 'es_ES':
			if ( isset( $tr_directory_label[ $label ] ) ) {
				$label = $tr_directory_label[ $label ];
			}
			break;
	}
	return $label;
}

add_filter( 'wpbdp_render_field_label', 'ink_wpbdp_field_label', 10, 1 );
/**
 * filter and translate descriptions
 *
 * @param string      $description input value
 *
 * @return string     $description or translation if found
 */
function ink_wpbdp_field_description( $description ) {
	$locale						 = get_locale();
	$tr_directory_description	 = get_directory_descriptions();
	switch ( $locale ) {
		case 'fr_FR':
		case 'de_DE':
		case 'es_ES':
			if ( isset( $tr_directory_description[ trim( $description ) ] ) ) {
				$description = $tr_directory_description[ trim( $description ) ];
			}
			break;
	}
	return $description;
}

add_filter( 'wpbdp_render_field_description', 'ink_wpbdp_field_description', 10, 1 );
/**
 * filter and translate terms link
 *
 * @param string      $input input value
 *
 * @return string     $link or translation if found
 */
function ink_community_terms( $input ) {
	return ink_tandc_link( $input );
}

//add_filter( 'wpbdp_settings_sanitize_terms-and-conditions', 'ink_community_terms', 10, 2);
add_filter( 'wpbdp_get_option_terms-and-conditions', 'ink_community_terms', 10, 1 );
/**
 * filter and translate login/registration link
 *
 * @param string      $input input value
 *
 * @return string     $link or translation if found
 */
function ink_account_url( $input ) {
	$url = ink_login_url( $input, '', false );
	return ($url) ? $url : $input;
}

add_filter( 'wpbdp_get_option_login-url', 'ink_account_url', 10, 1 );
add_filter( 'wpbdp_get_option_registration-url', 'ink_account_url', 10, 1 );

//while we are here, also filter the wp standard registration url
add_filter( 'register_url', 'ink_account_url', 10, 1 );
/**
 * when Business directory is enabled, use business directory listings as the author archive
 *
 * @param WP_Query &$this The WP_Query instance (passed by reference).
 */
function custom_post_author_archive( $query ) {
	if ( $query->is_author ) {
		if ( isset( $query->query_vars[ 'post_type' ] ) ) {
			switch ( $query->query_vars[ 'post_type' ] ) {
				case 'topic':
				case 'reply':
					return;
			}
		}
		$query->set( 'post_type', array( 'wpbdp_listing', 'post' ) );
		remove_action( 'pre_get_posts', 'custom_post_author_archive' );
	}
}

add_action( 'pre_get_posts', 'custom_post_author_archive', 1, 1 );


/*
 * enqueue css if this is enabled
 */
inkston_integration::get_instance()->ii_enqueue_script( 'ii-business-directory' );

/* use customizer settings for style, if available..
function ink_bd_customizer_style() {
	ink_get_theme_mod()....
}

wp_add_inline_style( 'ii-business-directory', ink_bd_customizer_style() );
*/
<?php

/*
 * controller for load of generic customization functions for
 * plugin integration and translation across inkston site family
 * which apply independently of theme
 *
 * Plugin approach:
 *  - plugin specific files do not need to check plugin loaded,
 *    the file is only loaded if the relevant plugins are available
 *  - plugin references in other files must do the check
 */
//functionality for inkston site family only
function is_inkston() {
	$sitefamily = strtolower( (is_multisite() ) ? ( get_blog_option( 1, 'blogname' ) ) : (get_bloginfo( 'name' )) );
	return ($sitefamily == 'inkston');
}

if ( is_inkston() ) {
	include_once( 'inkston.php' );
}

//general functions
include_once( 'arrays.php');
include_once( 'body-class.php');
if ( isset( $ii_options[ 'merge_comments' ] ) ) {
	include_once( 'comments.php');
}
include_once( 'customizer.php');
if ( isset( $ii_options[ 'disable_emoji' ] ) ) {
	include_once( 'disable-emoji.php');
}
include_once( 'excerpts.php');
if ( isset( $ii_options[ 'featured_posts' ] ) ) {
	include_once( 'featured-posts.php');
}
include_once( 'images.php');
if ( isset( $ii_options[ 'hashtags' ] ) ) {
	include_once( 'keywords.php');
}
include_once( 'links.php');
include_once( 'login.php');

//plugin customisations: avoid even loading/parsing file if plugin is not activated
if ( class_exists( 'BadgeOS' ) && isset( $ii_options[ 'badgeos_levels' ] ) ) {
	include_once( 'plug-badgeos.php' );
}
if ( class_exists( 'bbPress' ) && isset( $ii_options[ 'bbpress' ] ) ) {
	include_once( 'plug-bbpress.php' );
}
if ( function_exists( 'wpbdp' ) && isset( $ii_options[ 'bus_directory' ] ) ) {
	include_once( 'plug-business-directory.php' );
}
include_once( 'plug-contacts.php' );
//TODO: mailpoet test improvement
if ( function_exists( 'mailpoet_wp_version_notice' ) && isset( $ii_options[ 'mailpoet' ] ) ) {
	include_once( 'plug-mailpoet.php' );
}
if ( class_exists( 'Polylang' ) && isset( $ii_options[ 'polylang' ] ) ) {
	include_once( 'plug-polylang.php' );
}
if ( function_exists( 'relevanssi_build_index' ) ) {
	include_once( 'plug-relevanssi.php' );
}
if ( function_exists( 'the_champ_login_button' ) && isset( $ii_options[ 'socializer' ] ) ) {
	include_once( 'plug-socializer.php' );
}
if ( class_exists( 'woocommerce' ) ) {
	//controller file for various sets of woocommerce customizations
	include_once( 'plug-woo-.php' );
}

include_once( 'sharer.php' );
include_once( 'terms.php');
<?php

/*
 * Unless theme template is found, check this plugin templates and override woocommerce if present
 *
 * @param string $template		Template found by woocommerce.
 * @param string $template_name Template name.
 * @param string $template_path Template path. (default: '').
 */
function ii_locate_template( $template, $template_name, $template_path ) {

	//if theme template already located, no further processing: theme takes precedence
	if ( (strpos( $template, STYLESHEETPATH ) !== FALSE) || (strpos( $template, TEMPLATEPATH ) !== FALSE) ) {
		return $template;
	}

	$plugin_path = untrailingslashit( plugin_dir_path( __FILE__ ) ) . '/woocommerce/';
	if ( file_exists( $plugin_path . $template_name ) ) {
		$template = $plugin_path . $template_name;
	}


	return $template;
}

add_filter( 'woocommerce_locate_template', 'ii_locate_template', 10, 3 );

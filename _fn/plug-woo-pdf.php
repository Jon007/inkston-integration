<?php

/*
 * use pdf invoice template overrides provided by this plugin
 *
 * @param $file_path string WooCommerce PDF Invoices default template path
 * @param $type string	the filename without extension
 * @param $order
 * @return string filtered $file_path
 */
function ink_pdf_template_path( $file_path, $type, $order ) {
	$plugin_path = untrailingslashit( plugin_dir_path( __FILE__ ) ) . '/woocommerce/pdf/inkston-simple/';
	$file		 = $plugin_path . basename( $file_path );
	//$file		 = $plugin_path . $type . '.php';
	if ( file_exists( $file ) ) {
		$file_path = $file;
	}
	return $file_path;
}

add_filter( 'wpo_wcpdf_template_file', 'ink_pdf_template_path', 10, 3 );


/*
 * use pdf invoice css overrides provided by this plugin
 *
 * @param $file_path string WooCommerce PDF Invoices default template path
 * @return string filtered $file_path
 */
function ink_pdf_style_path( $file_path ) {
	$file = untrailingslashit( plugin_dir_path( __FILE__ ) ) . '/woocommerce/pdf/inkston-simple/style.css';
	if ( file_exists( $file ) ) {
		$file_path = $file;
	}
	return $file_path;
}

add_filter( 'wpo_wcpdf_template_styles_file', 'ink_pdf_style_path', 10, 1 );

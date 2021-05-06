<?php

/*
 * when this plugin is enabled, add css modifier to tax display
 */
inkston_integration::get_instance()->ii_enqueue_css( 'ii-tx' );

/*
 * overrides total display only for pdf invoices
 * woocommerce doesnt respect tax names and just produces one line labelled tax :(
 * @param array  $totals	order totals from woocommerce get_order_item_totals
 * @param WC_Order $order	woocommerce order
 * @param string $type	document type, ie invoice
 * @return array modified totals
 */
function ink_invoice_totals( $totals, $order, $type ) {
	if ( isset( $totals[ 'tax-1' ] ) ) {
	  $totals[ 'tax-1' ][ 'label' ] = 'VAT';
	}
	return $totals;
}

add_filter( 'wpo_wcpdf_woocommerce_totals', 'ink_invoice_totals', 10, 3 );

/*
 * Toggle VAT display for invoices.
 * Sadly the WooCommerce itemized option doesn't actually show VAT for each item
 * in a separate column, only the total itself is 'itemized' :(
 */
function ink_pdf_explicit_vat( $type, $Order_Document ) {
	$tax_display = get_option( 'woocommerce_tax_display_cart' );
	if ( wc_tax_enabled() && 'incl' == $tax_display ) {
		update_option( 'woocommerce_tax_display_cart', 'excl' );
		update_option( 'woocommerce_tax_total_display', 'itemized' );
	}
}

function ink_pdf_explicit_vat_off( $type, $Order_Document ) {
	if ( wc_tax_enabled() ) {
		update_option( 'woocommerce_tax_display_cart', 'incl' );
		update_option( 'woocommerce_tax_total_display', 'single' );
	}
}

add_action( 'wpo_wcpdf_before_pdf', 'ink_pdf_explicit_vat', 10, 2 );
add_action( 'wpo_wcpdf_after_pdf', 'ink_pdf_explicit_vat_off', 10, 2 );


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

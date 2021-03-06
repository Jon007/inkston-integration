<?php
/* 
 * customizations for made-to-order products, eg to submit design files etc
 * over plugin Alg_WC_Checkout_Files
 */

/*
 * add cart single flash message to explain about customization options
 */
function inkston_customization_cart_message() {
    $class_exists = class_exists( 'Alg_WC_Checkout_Files_Upload_Main');
    if (( is_cart() ) && ( $class_exists )) { //&& (class_exists( 'Alg_WC_Checkout_Files_Upload_Main') ) ) {
        global $AWCCF;
        //$awccf = new Alg_WC_Checkout_Files_Upload_Main;
        if ($AWCCF && $AWCCF->is_visible(1)) {
            wc_print_notice(__( 'Your shopping cart includes customization options, you can tell us about these on the checkout page.', 'inkston-integration'), 'notice');
        }
    }
}
add_action( 'woocommerce_before_cart', 'inkston_customization_cart_message');

/*
 * add checkout single flash message to explain about customization options
 */
function inkston_customization_checkout_message() {
    $class_exists = class_exists( 'Alg_WC_Checkout_Files_Upload_Main');
    if (( is_checkout() ) && ( $class_exists )) { //&& (class_exists( 'Alg_WC_Checkout_Files_Upload_Main') ) ) {
        //$awccf = new Alg_WC_Checkout_Files_Upload_Main;
        global $AWCCF;
        if ($AWCCF && ( $AWCCF->is_visible(1))) {
            wc_print_notice(__( 'Your order has a custom design option, if you like you can upload a file and/or make comments below. You may also skip this step and confirm details with us later.', 'inkston-integration'), 'notice');
        }
    }
}
add_action( 'woocommerce_before_checkout_form', 'inkston_customization_checkout_message');

/*
 * force inkston file label rather than simply please select a file..
 */
function ink_checkout_file_label( $pre_option, $option, $default ) {
	return __( "If you want to upload a file related to your order, press 'Choose file', select a file, then press 'upload'", 'inkston-integration' );
}

add_filter( 'pre_option_alg_checkout_files_upload_label_1', 'ink_checkout_file_label', 10, 3 );


<?php
/* 
 * customizations for made-to-order products, eg to submit design files etc
 * over plugin Alg_WC_Checkout_Files
 */

/*
 * add cart single flash message to explain about customization options
 */
function inkston_customization_cart_message()
{
    $class_exists = class_exists( 'Alg_WC_Checkout_Files_Upload_Main');
    if (( is_cart() ) && ( $class_exists )) { //&& (class_exists( 'Alg_WC_Checkout_Files_Upload_Main') ) ) {
        global $AWCCF;
        //$awccf = new Alg_WC_Checkout_Files_Upload_Main;
        if ($AWCCF && $AWCCF->is_visible(1)) {
            wc_print_notice(__( 'Your shopping cart includes customization options, you can tell us about these on the checkout page.', 'photoline-inkston'), 'notice');
        }
    }
}
add_action( 'woocommerce_before_cart', 'inkston_customization_cart_message');

/*
 * add checkout single flash message to explain about customization options
 */
function inkston_customization_checkout_message()
{
    $class_exists = class_exists( 'Alg_WC_Checkout_Files_Upload_Main');
    if (( is_checkout() ) && ( $class_exists )) { //&& (class_exists( 'Alg_WC_Checkout_Files_Upload_Main') ) ) {
        //$awccf = new Alg_WC_Checkout_Files_Upload_Main;
        global $AWCCF;
        if ($AWCCF && ( $AWCCF->is_visible(1))) {
            wc_print_notice(__( 'Your order has a custom design option, if you like you can upload a file and/or make comments below. You may also skip this step and confirm details with us later.', 'photoline-inkston'), 'notice');
        }
    }
}
add_action( 'woocommerce_before_checkout_form', 'inkston_customization_checkout_message');



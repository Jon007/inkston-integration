<?php
/* 
 * customizations for grouped products.
 */

function inkston_add_group_excerpt()
{
    global $product;
    if (($product) && ($product->get_type() == 'grouped')) {
        woocommerce_template_single_excerpt();
        remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 20);
    }
}
add_action( 'woocommerce_before_add_to_cart_form', 'inkston_add_group_excerpt', 10);
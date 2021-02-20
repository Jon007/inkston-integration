<?php
/* 
 * product bundle customizations which do not require translation
 */


/*
 * filter for adding on sale notices for bundles
 * 
 * @param bool  $on_sale     calculated by wooCommerce and overridden with high priority by currency switcher
 * @param WC_Product $product
 * 
 * @return bool     on sale or not
 */
function bundle_is_on_sale($on_sale, $product)
{
    if ($product && 'woosb' === $product->get_type()) {
        $woosb_pct = intval(get_post_meta($product->get_id(), 'woosb_price_percent', true));
        if (($woosb_pct) && ($woosb_pct < 100)) {
            return true;
        }
    }
    return $on_sale;
}
add_filter( 'woocommerce_product_is_on_sale', 'bundle_is_on_sale', 10000, 2);

/*
 * filter for adding on sale flash notices 
 * 
 * @param string  $output     WooCommerce output
 * @param Post       $post
 * @param WC_Product $product
 * 
 * @return bool     on sale or not
 */
function custom_product_sale_flash($output, $post, $product)
{

    if (!$product) {
        return $output;
    }

    $woosb_pct = 100;
    if ($product && 'woosb' === $product->get_type()) {
        $woosb_pct = intval(get_post_meta($product->get_id(), 'woosb_price_percent', true));
        if (($woosb_pct) && ($woosb_pct < 100)) {
            //last check for fixed price rather than percent
            $woosb_fixed = intval(get_post_meta($product->get_id(), '_price', true));
            if (($woosb_fixed) && ($woosb_fixed == $woosb_pct)) {
                return $output;
            }
            return '<span class="onsale">-' . round(100 - $woosb_pct) . '% ' . '</span>';
        }
    }

    return $output;
}
add_filter( 'woocommerce_sale_flash', 'custom_product_sale_flash', 11, 3);

//standardise smart bundle button
function bundle_add_to_cart_text($text, $product){
    $text = $product->is_purchasable() && $product->is_in_stock() ? __( 'Add to cart', 'woocommerce' ) : __( 'Read more', 'woocommerce' );
    return $text;
}
add_filter( 'woosb_product_single_add_to_cart_text', 'bundle_add_to_cart_text', 10, 2);
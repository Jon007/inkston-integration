<?php
/* 
 * gift coupon enhancements
 */


/* 
 * enables rich text editor for gift messages
 * 
 * @param string $gift_message_input    default html textarea with message
 * @param string $gift_message          message only
 * 
 * @return string                       filtered html input box
 */
function ink_gift_message_input($gift_message_input, $gift_message)
{
    return wp_editor($gift_message, 'wcs_gift_message', array(
        'default_editor' => 'TinyMCE',
        'teeny' => false,
        ));
}
add_filter( 'wcs_gift_message_input', 'ink_gift_message_input', 10, 2);


/**
 * Filter coupon metadata (rules) for gift coupons created by woo-sell-coupons
 * 
 * @param array       $coupon_meta default coupon meta data
 * 
 * @return array      filtered meta data.
 */
function ink_gift_coupon_rules($coupon_meta, $id, $coupon_code)
{

    $coupon_meta['exclude_product_categories'] = array(5278, 5273);
    $coupon_val = $coupon_meta['coupon_amount'];
    $coupon_meta['minimum_amount'] = $coupon_val;
    /*  too clever, doesn't quite work..
      if (isWoocs()) {
      global $WOOCS;
      $level = $WOOCS->woocs_exchange_value($level);
      }
      $coupon_val = wc_price($coupon_val);
     */
    $coupon_val .= 'USD';
    $coupon_meta['_wjecf_enqueue_message'] = sprintf(
        __( 'The coupon %s will give you a discount of %s when your basket value reaches %s or more.', 'inkston-integration')
        , $coupon_code, $coupon_val, $coupon_val);
    return $coupon_meta;
}
add_filter( 'wcs_gift_coupon_meta', 'ink_gift_coupon_rules', 10, 3);

/**
 * apply special formatting to gift coupon including link to auto-add-coupon to basket
 * ( ?apply_coupon=coupon_code requires plugin, not implemented in woocommerce core)
 * could also add fancy formatting / additional message and QR codes
 *
 * @param string $formatted_coupon_code default formatting
 * @param string $coupon_code           raw coupon code
 * @param string $coupon_amount         raw coupon amount
 * @param string $formatted_price       formatted coupon amount
 */
function ink_format_gift_coupon($formatted_coupon_code, $coupon_code, $coupon_amount, $formatted_price)
{
    global $woocommerce;
    if ($woocommerce) {
//        $cart_url = wc_get_cart_url();
        //      $formatted_coupon_code = sprintf(__( 'Click to add %s saving to your shopping basket.', 'inkston-integration'), $formatted_price );
        $formatted_coupon_code = '<h2 style="text-align:center;"><a class="saleflash" href="' . wc_get_cart_url() .
            '?apply_coupon=' . $coupon_code . '">' . $coupon_code . '</a></h2>';
    }
    return $formatted_coupon_code;
}
add_filter( 'wcs_format_gift_coupon', 'ink_format_gift_coupon', 10, 4);


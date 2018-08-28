<?php

/*
 * If this module is enabled, new stock should default to
 * 		Manage Stock,
 * 		On Back Order (if stock zero) or In Stock (if stock is set)
 * 		Backorders allowed
 *
 * Meta to consider:
 * 	'_stock_status':ideally, default to 'instock' for new items, if stock will be set
 *                           or 'onbackorder' if no stock to be set
 * 	'_manage_stock':ideally default to 'yes' [for simple products and variations]
 * 	'_stock': default should be zero, or parameterized
 *  '_backorders':  ideally default to 'notify'
 *
 * Events to consider:
 *		1. & 2. MPD multisite post duplicator new/update
 *			implemented in mpd module which checks the backorders option on the target site
 * 		3. Set default values in new product screen (desirable, not essential)
 * 		4. Default save of product [to handle copy actions and other saves within site,
 * 			where not done via user interface]: DO NOT IMPLEMENT
 *			NO action to avoid interfering with cloning and other save operations
 *
 */

/*
 * replace text Back-ordered on orders
 *
 * @param $display_key string	meta key of order meta
 * @param $meta array	order meta key and value
 * @param $orderitem WC_Order_Item current order item
 * @return string filtered $display_key
 */
function ink_backordered( $display_key, $meta, $orderitem ) {
	if ( $display_key == 'Back-ordered' ) {
		$ii_options			 = ii_get_options();
		$backordermessage	 = ( isset( $ii_options[ 'backordered' ] )) ? $ii_options[ 'backordered' ] : '';
		if ( $backordermessage ) {
			$display_key = ink_add_backorder_link( $backordermessage );
		}
	}
	return $display_key;
}

add_filter( 'woocommerce_order_item_display_meta_key', 'ink_backordered', 10, 3 );

/*
 * replace backorder availability text on product pages
 *
 * @param $availability string	meta key of order meta
 * @param $product WC_Product the current product
 * @return string filtered $availability
 */
function ink_backorder_availability( $availability, $product ) {
	if ( $product->managing_stock() && $product->is_on_backorder( 1 ) && $product->backorders_require_notification() ) {
		$availability = ink_backorder_availability_text( $availability );
	}
	return $availability;
}

add_filter( 'woocommerce_get_availability_text', 'ink_backorder_availability', 10, 2 );

/*
 * replace backorder availability text on cart pages
 *
 * @param $availability string	meta key of order meta
 * @return string filtered $availability
 */
function ink_backorder_availability_text( $availability ) {
		$ii_options			 = ii_get_options();
		$backordermessage	 = ( isset( $ii_options[ 'willbackorder' ] )) ? $ii_options[ 'willbackorder' ] : '';
		if ( $backordermessage ) {
		$availability = $backordermessage;
	}
	return $availability;
}

function ink_backorder_availability_cart( $backorderhtml ) {
	$availability = ink_backorder_availability_text( '' );
	if ( $availability ) {
		$backorderhtml = '<p class="backorder_notification">' . ink_add_backorder_link( $availability ) . '</p>';
	}
	return $backorderhtml;
}

add_filter( 'woocommerce_cart_item_backorder_notification', 'ink_backorder_availability_cart', 10, 1 );

/*
 * this could [conditionally] force allow back orders
 * but would then be at odds with product edit user interface etc
 * and hard/impossible to override conditions
 */
/*
function ink_backorders_allowed( $allowed, $productid, $product ) {
	return true;
}
add_filter( 'woocommerce_product_backorders_allowed', 'ink_backorders_allowed', 10, 3 );
*/

/*
 * this could change default save behaviour, but would lead to unexpected behaviour
 * due to over-riding product edit user interface and explicit properties set on clone
 *
function ink_change_product_defaults( $postID, $post, $update ) {
	if ( ! $update ) {//  $update is false if we're creating a new post
		// if stock status is in stock, then also set to Manage Stock
		update_post_meta( $post->ID, '_manage_stock', 'yes' );
	}
}
add_action( 'save_post_product', 'ink_change_product_defaults', 10, 3 );
 */


/*  TODO: change defaults for new product screen, example from
 * https://stackoverflow.com/questions/15908411/get-woocommerce-to-manage-stock-as-a-default
add_action( 'admin_enqueue_scripts', 'wc_default_variation_stock_quantity' );
function wc_default_variation_stock_quantity() {
  global $pagenow, $woocommerce;

  $default_stock_quantity = 1;
  $screen = get_current_screen();

  if ( ( $pagenow == 'post-new.php' || $pagenow == 'post.php' || $pagenow == 'edit.php' ) && $screen->post_type == 'product' ) {

    ?>
<!-- uncomment this if jquery if it hasn't been included
    <script src="//ajax.googleapis.com/ajax/libs/jquery/1.8.2/jquery.min.js"></script>
-->
    <script type="text/javascript">
    jQuery(document).ready(function(){
        if ( !jQuery( '#_manage_stock' ).attr('checked') ) {
          jQuery( '#_manage_stock' ).attr('checked', 'checked');
        }
        if ( '' === jQuery( '#_stock' ).val() ) {
          jQuery( '#_stock' ).val(<?php echo $default_stock_quantity; ?>);
        }
    });
    </script>
    <?php
  }
}
*/

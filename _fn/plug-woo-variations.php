<?php

/*
 * WooCommerce Variation-related enhancements
 */

/*
 * When bulk editing, woocommerce silently omits variations from updates
 * by hooking woocommerce_product_bulk_edit_save we can
 * allow price to be applied to variations
 *
 * Example scenarios:  increase all prices in category by 10%
 * TODO: extend to support:
 * 		add 10% off sale price to all products in category
 * 		update additional attributes like shipping class across variations etc
 *
 * @param WC_Product_Variable $product     this is the product already processed by bulk edit.
 */
function ink_bulk_update_variable_product_prices( $product ) {
	if ( $product && 'variable' === $product->get_type() ) {
		if ( ! empty( $_REQUEST[ 'change_regular_price' ] ) && isset( $_REQUEST[ '_regular_price' ] ) ) { // WPCS: input var ok, sanitization ok.
			$change_regular_price	 = absint( $_REQUEST[ 'change_regular_price' ] ); // WPCS: input var ok, sanitization ok.
			$raw_regular_price		 = wc_clean( wp_unslash( $_REQUEST[ '_regular_price' ] ) ); // WPCS: input var ok, sanitization ok.
			$is_percentage			 = ( bool ) strstr( $raw_regular_price, '%' );
			//woocommerce uses formatted decimal here but could be issue as we need to do calculations
			//$regular_price			 = wc_format_decimal( $raw_regular_price );
			$regular_price			 = wc_format_decimal( $raw_regular_price );
			if ( ! is_numeric( $regular_price ) ) {
				error_log( 'ink_bulk_update_variable_product_prices could no interpret new price: ' . $raw_regular_price );
			}
			ink_update_variation_prices( $product, $change_regular_price, $is_percentage, $regular_price );
		}
	}
}

add_action( 'woocommerce_product_bulk_edit_save', 'ink_bulk_update_variable_product_prices', 10, 1 );

/*
 * actually do the work of updating the price
 * based on woocommerce function bulk_edit_save(), applied to variations
 *
 * @param WCProduct $product		this is the product already processed by bulk edit.
 * @param int $change_regular_price three way option to set absolute price, increase or decrease
 * @param bool $is_percentage		is the new price a percentage change?
 * @param float	$regular_price		the new price
 */
function ink_update_variation_prices( $product, $change_regular_price, $is_percentage, $regular_price ) {

	$productVariations = $product->get_children();
	if ( empty( $productVariations ) ) {
		return false;
	}
	foreach ( $productVariations as $variation_id ) {
		$changed	 = false;
		$variation	 = wc_get_product( $variation_id );
		if ( $variation ) {
			//logic as per bulk_edit_save(), refocussed to variation rather than parent product
			$old_regular_price = $variation->get_regular_price();
			switch ( $change_regular_price ) {
				case 1:
					$new_price = $regular_price;
					break;
				case 2:
					if ( $is_percentage ) {
						$percent	 = $regular_price / 100;
						$new_price	 = $old_regular_price + ( round( $old_regular_price * $percent, wc_get_price_decimals() ) );
					} else {
						$new_price = $old_regular_price + $regular_price;
					}
					break;
				case 3:
					if ( $is_percentage ) {
						$percent	 = $regular_price / 100;
						$new_price	 = max( 0, $old_regular_price - ( round( $old_regular_price * $percent, wc_get_price_decimals() ) ) );
					} else {
						$new_price = max( 0, $old_regular_price - $regular_price );
					}
					break;

				default:
					break;
			}

			if ( isset( $new_price ) && $new_price !== $old_regular_price ) {
				$changed	 = true;
				$new_price	 = round( $new_price, wc_get_price_decimals() );
				$variation->set_regular_price( $new_price );
			}
			if ( $changed ) {
				$variation->save();
				updateTranslations( $variation, $new_price );
			}
		}
	}
}

/*
 * when saving a variation, update the related translations
 * @param WCProduct $variation		this is the variation just saved
 * @param float	$new_price			the new price
 */
function updateTranslations( $variation, $new_price ) {
	if ( function_exists( 'pll_languages_list' ) && function_exists( 'get_translated_variation' ) ) {
		$langs = pll_languages_list();
		foreach ( $langs as $lang ) {
			$translation_id			 = get_translated_variation( $variation->get_id(), $lang );
			$translationVariation	 = wc_get_product( $translation_id );
			if ( $translationVariation && ($translationVariation->get_id() != $variation->get_id() ) ) {
				$translationVariation->set_regular_price( $new_price );
				$translationVariation->save();
			}
		}
	}
}

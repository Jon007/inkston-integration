<?php

/*
 * general woocommerce polylang customizations plus
 * translation customisations for other woocommerce extensions if these are loaded
 */
if ( class_exists( 'WPcleverWoosb' ) && isset( $ii_options[ 'bundletrans' ] ) ) {
	include_once( 'plug-woo-poly-product-bundles.php' );
}
if ( isset( $ii_options[ 'merge_comments' ] ) ) {
	include_once( 'plug-woo-poly-reviews.php' );
}
/**
 * Polylang meta filter, if true meta item will not be synchronized.
 *
 *
 * @param string      $meta_key Meta key
 * @param string|null $meta_type
 * @return bool True if the key is protected, false otherwise.
 */
function allowSyncCurrencyMeta( $protected, $meta_key, $meta_type ) {
	$meta_prefix = '_alg_currency_switcher_per_product_';
	$length		 = strlen( $meta_prefix );
	if ( substr( $meta_key, 0, $length ) === $meta_prefix ) {
		return false;
	} else {
		return $protected;
	}
}

add_filter( 'is_protected_meta', 'allowSyncCurrencyMeta', 10, 3 );

/*
 * get the product, if it is a variable product, get the parent
 *
 * @param int $product_id   Product
 *
 * @return WC_Product|null the Product
 */
function get_product_or_parent( $product_id ) {
	$product = wc_get_product( $product_id );
	if ( $product && 'variation' === $product->get_type() ) {
		//ok, find translated variation
		$product = wc_get_product( $product->get_parent_id() );
	}
	return $product;
}

/*
 * get the translated product, including if it is a variation product, get the translated variation
 * if there is no translation, return the original product
 *
 * @param int $product_id   Product
 *
 * @return int    translated product or variation (or original if no translation)
 *
 */
function get_translated_variation( $product_id, $lang ) {
	//if input is already in correct language just return it
	$sourcelang = pll_get_post_language( $product_id );
	if ( $sourcelang == $lang ) {
		return $product_id;
	}
	//if a translated item is found, return it
	$translated_id = pll_get_post( $product_id, $lang );
	if ( ( $translated_id ) && ( $translated_id != $product_id ) ) {
		return $translated_id;
	}
	//ok no linked Polylang translation so maybe it's a variation
	$product = wc_get_product( $product_id );
	if ( $product && 'variation' === $product->get_type() ) {
		//it's a variation, so let's get the parent and translate that
		$parent_id		 = $product->get_parent_id();
		$translated_id	 = pll_get_post( $parent_id, $lang );
		//if no translation return the original product variation id
		if ( ( ! $translated_id) || ($translated_id == $parent_id) ) {
			return $product_id;
		}
		//ok, it's a variation and the parent product is translated, so here's what to do:
		//find the master link for this variation using the Hyyan '_point_to_variation' key
		$variationmaster = get_post_meta( $product_id, '_point_to_variation' );
		if ( ! $variationmaster ) {
			return $product_id;
		}
		//and now the related variation for the translation
		$posts = get_posts( array(
			'meta_key'		 => '_point_to_variation',
			'meta_value'	 => $variationmaster,
			'post_type'		 => 'product_variation',
			'post_parent'	 => $translated_id,
		) );

		if ( count( $posts ) ) {
			return $posts[ 0 ]->ID;
		}
	}
}

function ii_unload_email_textdomains() {
	unload_textdomain( 'inkston-integration' );
	unload_textdomain( 'woo-advanced-shipment-tracking' );
}

add_action( 'woo-poly.Emails.switchLanguage', 'ii_unload_email_textdomains' );
function ii_load_email_textdomains() {
	inkston_integration::load_textdomain();
	global $WC_advanced_Shipment_Tracking;
	if ( $WC_advanced_Shipment_Tracking ) {
		$WC_advanced_Shipment_Tracking->wst_load_textdomain();
	}
}

add_action( 'woo-poly.Emails.afterSwitchLanguage', 'ii_load_email_textdomains' );
function ii_shipping_provider_url_template( $provider_url, $provider_name ) {
	$localePos = strpos( $provider_url, '/en' );
	if ( $localePos ) {
		$localeCode = pll_current_language();
		if ( $localeCode != 'en' ) {
			$replaceWith	 = '/' . $localeCode;
			$provider_url	 = str_replace( '/en', $replaceWith, $provider_url );
		}
	}
	return $provider_url;
}

add_filter( 'shipping_provider_url_template', 'ii_shipping_provider_url_template', 10, 2 );

<?php
/*
 * generate links for amazon reviews
 */

/*
 * Output please leave review on Amazon links for products with ASINs
 * (these are valid across sites as link is to ASIN not to vendor)
 *
 * currently used by custom woocommerce template single-product-reviews.php
 * TODO: hook an action instead of depending on template override
 */
function ink_output_please_leave_reviews() {
	$ii_options = ii_get_options();
	if ( (class_exists( 'woocommerce' )) && is_product() ) {
		global $product;
		if ( $product ) {
			$amazonEuLink	 = $amazonUsLink	 = '';
			$productid		 = $product->get_id();
			$asin			 = get_post_meta( $productid, 'asin', true );
			if ( $asin ) {
				$amazonEuLink = ink_amazon_link( $asin, true, true );
			}
			$asinusa = get_post_meta( $productid, 'asinusa', true );
			if ( ! $asinusa && $asin ) {
				$asinusa = $asin;
			}
			if ( $asinusa && $asinusa != 'NONE' ) {
				$amazonUsLink = ink_amazon_link( $asinusa, false, true );
			}
			if ( $amazonEuLink || $amazonUsLink ) {
				?><div class="reviews"><?php
					_e( 'Please leave your reviews!', 'inkston-integration' );
					echo ( ' ');
					?></div><?php
				if ( $amazonEuLink && isset( $ii_options[ 'amazoneu' ] ) ) {
					//TODO: UNCOMMENT temp comment while Amazon EU disabled
					echo $amazonEuLink . ' ';
				}
				if ( $amazonUsLink && ($ii_options[ 'amazonusa' ]) ) {
					echo $amazonUsLink;
				}
			}
		}
	}
}

/*
 * Format ASIN as an Amazon Link
 *
 * @param string  $asin     Amazon product id
 * @param bool    $isEU     set to return EU formatted link for locale, otherwise Amazon USA
 * @param bool    $isReview set to output review link, otherwise outputs product link
 * @return string formatted output
 */
function ink_amazon_link( $asin, $isEU, $isReview ) {
	if ( ! $asin ) {
		return;
	}
	if ( ! is_array( $asin ) && strpos( $asin, ',' ) ) {
		$asin = explode( ',', $asin );
	}
	if ( is_array( $asin ) ) {
		$output = '';
		foreach ( $asin as $variation ) {
			if ( $variation ) {
				$output .= _ink_amazon_link( $variation, $isEU, $isReview, true ) . ' ';
			}
		}
		return $output;
	} else {
		return _ink_amazon_link( $asin, $isEU, $isReview, false );
	}
}

/*
 * Format ASIN as an Amazon Link
 *
 * @param string  $asin         Amazon product id
 * @param bool    $isEU         set to return EU formatted link for locale, otherwise Amazon USA
 * @param bool    $isReview     set to output review link, otherwise outputs product link
 * @param bool    $isVariation  is a product variation
 *
 * @return string formatted output
 */
function _ink_amazon_link( $asin, $isEU, $isReview, $isVariation ) {
	if ( ! $asin || $asin == '' || $asin == 'NONE' ) {
		return;
	}
	$amazon_domain	 = '.com';
	$amazon_verb	 = ($isReview) ? __( 'Review', 'inkston-integration' ) : __( 'View', 'inkston-integration' );
	$amazon_site	 = '';
	if ( $isEU ) {
		$locale = get_locale();
		switch ( $locale ) {
			case 'fr_FR':
				$amazon_site	 = 'Amazon France';
				$amazon_domain	 = '.fr';
				break;
			case 'es_ES':
				$amazon_site	 = 'Amazon España';
				$amazon_domain	 = '.es';
				break;
			default:
				$amazon_site	 = 'Amazon UK';
				$amazon_domain	 = '.co.uk';
		}
	} else {
		$amazon_site = __( 'Amazon USA', 'inkston-integration' );
	}
	$amazon_title	 = $amazon_verb . ' ' . $asin . ' ' . __( 'on', 'inkston-integration' ) . ' ' . $amazon_site;
	$amazonurl		 = ($isReview) ? 'https://www.amazon' . $amazon_domain .
	'/review/create-review/ref=cm_cr_dp_d_wr_but_btm?ie=UTF8&asin=' . $asin . '#' : 'https://www.amazon' . $amazon_domain . '/dp/' . $asin . '/';

	return '<span class="amazon-link"><a title="' . $amazon_title . '" href="' .
	$amazonurl . '" target="_blank">' .
	( ($isVariation) ? $asin . ' ' . __( 'on', 'inkston-integration' ) . ' ' : '')
	. $amazon_site . '</a></span>';
}

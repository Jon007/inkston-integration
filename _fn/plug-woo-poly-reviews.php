<?php
/*
 * merge product reviews across different languages
 * similar in concept to merge_comments in comments
 */

/* remove wooCommerce reviews tab since reviews are now shown at end of page */
function inkston_remove_reviews_tab( $tabs ) {
	unset( $tabs[ 'reviews' ] );
	return $tabs;
}

add_filter( 'woocommerce_product_tabs', 'inkston_remove_reviews_tab', 98 );

/*
 *  What this does is:
 * 	1) find the category for this product with the lowest number of products
 * 	    this will be used as the source for reviews
 * 	2) add in reviews for other languages for this product category
 */
function inkston_merged_reviews() {
	$other_language				 = (pll_current_language() == 'en') ? "es" : "en";
	$least_products				 = 999999;   //get the term with least products
	$term_name					 = '';
	$term_slug					 = '';
	$other_language_term_slugs	 = '';

	global $post;
	$terms = get_the_terms( $post->ID, 'product_cat' );
	if ( ! ($terms) ) {
		return;
	}   //if there are no terms then omit the comments from term...
	foreach ( $terms as $term ) {
		if ( $term->count < $least_products ) {
			$least_products	 = $term->count;
			$termid			 = $term->term_id;
			$term_name		 = $term->name;
			$term_slug		 = $term->slug;
		}
	}
	//get additional languages here after deciding which is the finally selected term
	?><h2 class="woocommerce-Reviews-title category-reviews"><?php echo(__( 'Recent discussions in: ', 'inkston-integration' ) . $term_name ); ?></h2><?php
	// make action magic happen here...
	//echo do_shortcode( '[decent_comments number="25" taxonomy="product_cat" terms="' . $term_slug . '" ]' );
	$langs	 = pll_languages_list();
	$termids = array();
	foreach ( $langs as $lang ) {
		$translated_term = pll_get_term( $termid, $lang );
		if ( $translated_term ) {
			$termids[] = $translated_term;
		}
	}
	polylang_remove_comments_filter();
	echo do_shortcode( '[decent_comments number="50" taxonomy="product_cat" term_ids="' . implode( ',', $termids ) . '" ]' );
	comments_template();
}

add_action( 'woocommerce_after_single_product_summary', 'inkston_merged_reviews', 90 );

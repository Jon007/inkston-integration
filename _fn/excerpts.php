<?php

/*
 * custom excerpt handling
 */
/**
 * Add metabox Excerpt for Page.
 */
function inkston_add_excerpt_to_pages() {
	add_post_type_support( 'page', 'excerpt' );
}

add_action( 'init', 'inkston_add_excerpt_to_pages' );


/*
 * output excerpt with readmore link
 */
function inkston_excerpt( $length = 30, $readmore = false ) {
	global $post;
	$id		 = $post->ID;
	$output	 = '<p class="inkston-excerpt">' . inkston_get_excerpt( inkston_excerpt_length( $length ) );
	if ( $readmore == true ) {
		$readmore_link	 = '<span class="inkston-readmore"><a href="' . get_permalink( $id ) . '" title="' . __( 'reading', 'inkston-integration' ) . '" rel="bookmark">' . __( 'Read more', 'inkston-integration' ) . '</a></span>';
		$output			 .= apply_filters( 'inkston_readmore_link', $readmore_link );
	}
	$output .= '</p>';
	echo $output;
}

/*
 * return excerpt text
 */
function inkston_get_excerpt( $length = 25, $readmore = false, $postid = 0 ) {
	global $post;
	$thisPost	 = $post;
	$output		 = '';
	if ( is_search() && 13 == $length ) {
		$length = inkston_excerpt_length( $length );
	}
	if ( $postid ) {
		$id			 = $postid;
		$thisPost	 = get_post( $id );
	} else {
		$id = $post->ID;
	}
	if ( has_excerpt( $id ) ) {
		$output = get_the_excerpt( $id );
	}
	if ( $output == '' ) {
		if ( $post->post_type == 'wpbdp_listing' ) {
			$output = $thisPost->post_content;
		} else {
//			$output = get_the_content();
			$output = $thisPost->post_content;
		}
	}
	if ( $output && $output != '' ) {
		$output = wp_trim_words( strip_shortcodes( $output ), $length );
	}

	//if it is a search, can't use the price_html as this includes shortcodes which are not then interpreted
	//TODO: optionally, do shortcodes in price html
	if ( ( ! is_search()) && ($post->post_type == 'product') ) {
//	if ( $post->post_type == 'product' ) {
		if ( class_exists( 'woocommerce' ) ) {
			$product = wc_get_product( $post );
			//quick check for product with no description
			if ( $output == '' ) {
				$output = $product->get_name();
			}
			if ( ! empty( $product->get_price() ) ) {
			    $output .= $product->get_price_html();
		    }
	    }
	}
	return $output;
}

/*
 * filters get_the_excerpt()
 * handles socializer default excerpts
 * adds basic excerpt for projects with no excerpts
 * adds hashtags
 */
function inkston_filter_excerpt( $excerpt ) {
	global $post;
	if ( $post ) {
		if ( ! $excerpt || $excerpt == 'Spread the love' ) {  //bogus excerpt creeping in from SuperSocializer
			if ( $post->post_type == 'wpbdp_listing' ) {
				$excerpt = $post->post_content;
			} else {
				$excerpt = get_the_content();
			}
			$excerpt = wp_trim_words( strip_shortcodes( $excerpt ), inkston_excerpt_length( 36 ) );
		}

		//if a woocommerce product always add price and buy link to excerpt
		if ( ($post->post_type == 'product') && (class_exists( 'woocommerce' ) ) ) {
			$product = wc_get_product( $post );
			if ( $product ) {
				//quick check for product with no description
				if ( $excerpt == '' ) {
					$excerpt = $product->get_name();
					$excerpt .= ' ' . $product->get_price_html();
				}
			}
		}
	}

	if ( ( is_feed() ) || ( stripos( $_SERVER[ 'REQUEST_URI' ], '/feed' ) ) ) {
		$excerpt = strip_shortcodes( $excerpt );
		if ( $post && function_exists( 'ink_wp_hashtags' ) ) {
			$excerpt .= ink_wp_hashtags( $post );
		}
	}
	return $excerpt;
}

add_filter( 'get_the_excerpt', 'inkston_filter_excerpt', 10, 1 );
/**
 * Change default excerpt read more style
 */
function inkston_excerpt_more( $more ) {
	return '...';
}

add_filter( 'excerpt_more', 'inkston_excerpt_more' );
/**
 * Shorten excerpt length
 */
function inkston_excerpt_length( $length ) {
	$ii_options = ii_get_options();
	if ( isset( $ii_options[ 'excerpt_length' ] ) ) {
		$length = intval( $ii_options[ 'excerpt_length' ] );
	}
	if ( ! $length ) {
		if ( is_sticky() && is_front_page() && ! is_home() ) {
			$length = 90;
		} elseif ( is_sticky() && is_home() || is_sticky() && ! is_home() && ! is_front_page() ) {
			$length = 28;
		} elseif ( is_home() ) {
			$length = 35;
		} elseif ( is_page() ) {
			$length = 30;
		} else {
			$length = 30;
		}
	}
	return $length;
}

add_filter( 'excerpt_length', 'inkston_excerpt_length', 999 );

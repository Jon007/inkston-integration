<?php

/*
 * woocommerce enhancement for SEO
 */


/*
 * if on Shop page, suppress SEO Next link
 * TODO: check if this is needed
 */
function inkston_suppress_shop_next_link( $link ) {
    //if (is_page( wc_get_page_id( 'shop' ) )){
    if ( is_shop() ) {
	return '';
    } else {
	return $link;
    }
}

add_filter( 'wpseo_next_rel_link', 'inkston_suppress_shop_next_link', 10, 1 );

/*
 * change opengraph type to product on product pages
 */
function inkston_type_product( $type ) {
    if ( is_product() ) {
	return "product";
    } else {
	if ( $type ) {
	    return $type;
	} else {
	    return "article";
	}
    }
}

add_filter( 'wpseo_opengraph_type', 'inkston_type_product' );

/*
 * add opengraph namespace for products
 */
function og_product_namespace( $input ) {
    if ( is_singular( 'product' ) ) {
	$input = preg_replace( '/prefix="([^"]+)"/', 'prefix="$1 product: http://ogp.me/ns/product#"', $input );
    }

    return $input;
}

add_filter( 'language_attributes', 'og_product_namespace', 11 );

/*
 * add meta tags for products to product pages
 */
function inkston_product_meta() {
    if ( is_product() ) {
	global $product;
	//configure fb app_id in yoast
	//echo ( '<meta property="fb:app_id" content="278896005827382" />');
	//already output by Yoast, should not be repeated
	//echo ( '<meta property="og:type"   content="product" />' . "\r\n");
	//echo ( '<meta property="og:brand" content="Inkston" />' . "\r\n");
	//og:brand reported not valid for og type product
	//ink_tag_from_tax( 'og:brand', 'pa_brand', $product);
	ink_tag_from_tax( 'product:brand', 'pa_brand', $product );
		//call get price with 'edit' to get unfiltered base currency price
		echo ( '<meta property="product:price:amount" content="' . esc_attr( $product->get_price( 'edit' ) ) . '"/>' . "\r\n");
		//get option for base currency: cannot use get_woocommerce_currency() as this may be filtered by currency switcher
		echo ( '<meta property="product:price:currency" content="' . get_option( 'woocommerce_currency' ) . '" />' . "\r\n");
	ink_tag_from_tax( 'product:category', 'product_cat', $product );
	ink_tag_from_tax( 'product:material', 'pa_materials', $product );

	ink_tag_from_meta( 'product:retailer', 'inkston', $product );
	ink_tag_from_meta( 'product:retailer_part_no', '_sku', $product );
	ink_tag_from_meta( 'product:upc', 'upc', $product );

	echo ( '<meta property="product:is_product_shareable" content="1" />' . "\r\n");
	echo ( '<meta property="product:age_group" content="adult" />' . "\r\n");
	if ( $product->is_in_stock() ) {
	    echo ( '<meta property="product:availability" content="instock" />' . "\r\n");
	}
	ink_tag_dimensions( $product );
    }
}

//add_action( 'wp_head', 'inkston_product_meta' );
//TODO: wpseo_opengraph deprecated in v14, use Presenter instead see https://developer.yoast.com/blog/yoast-seo-14-0-adding-metadata/
//as of 20/2/2021 still working
//add_action( 'wpseo_opengraph', 'inkston_product_meta', 40 );
add_action( 'wpseo_head', 'inkston_product_meta', 30 );
/*
 * include product dimensions in meta tags, if set
 */
function ink_tag_dimensions( $product ) {
    $net_weight = get_post_meta( $product->get_id(), 'net_weight', false );
    if ( $net_weight ) {
	if ( is_array( $net_weight ) ) {
	    $net_weight = recursive_filter_implode( ', ', $net_weight );
	}
    } elseif ( $product->has_weight() ) {
	$net_weight = $product->get_weight();
    }
    if ( $net_weight ) {
	echo ( '<meta property="product:weight:units" content="g" />' . "\r\n");
	echo ( '<meta property="product:weight:value" content="' . $net_weight . '" />' . "\r\n");
    }


    $net_size = get_post_meta( $product->get_id(), 'net_size', true );
    if ( $net_size ) {
	$net_size = esc_html( wc_format_dimensions( $net_size ) );
	if ( $net_size == __( 'N/A', 'woocommerce' ) ) {
	    $net_size = '';
	}
    } elseif ( $product->has_dimensions() ) {
	$net_size = esc_html( wc_format_dimensions( $product->get_dimensions( false ) ) );
    }

    if ( $net_size ) {
	echo ( '<meta property="product:size" content="' . $net_size . '" />' . "\r\n");
    }
}

/*
 * create tags from post meta values
 * .. not currently used?
 */
function ink_tag_from_meta( $tag, $meta, $post ) {
    $post_id = ($post->post_type == 'product') ? $post->get_id() : $post->id;
    $values	 = get_post_meta( $post_id, $meta );
    if ( is_array( $values ) ) {
	foreach ( $values as $value ) {
	    if ( is_array( $value ) ) {
		$value = implode( ',', array_diff( $value, array( '' ) ) );
	    }
	    echo ( '<meta property="' . $tag .
	    '" content="' . esc_attr( $value ) . '"/>' . "\r\n");
	}
    }
}

/*
 * create meta tags for product attributes
 * used by inkston_product_meta()
 */
function ink_tag_from_tax( $tag, $tax, $post ) {
    $post_id = ($post->post_type == 'product') ? $post->get_id() : $post->id;
    $values	 = get_the_terms( $post_id, $tax );
    if ( is_array( $values ) ) {
	foreach ( $values as $value ) {
	    if ( is_array( $value ) ) {
		$value = implode( ',', array_diff( $value, array( '' ) ) );
	    }
	    echo ( '<meta property="' . $tag .
	    '" content="' . esc_attr( $value->name ) . '"/>' . "\r\n");
	}
    } else {
	//default values
	$defaultvalue = '';
	switch ( $tax ) {
	    case 'pa_brand':
		$defaultvalue = 'inkston';
	    default:
	}
	if ( $defaultvalue ) {
	    echo ( '<meta property="' . $tag .
	    '" content="' . esc_attr( $defaultvalue ) . '"/>' . "\r\n");
	}
    }
}

/*
 * Yoast enable product price parameter
 */
function register_replacements() {
    wpseo_register_var_replacement(
    'wc_price', 'get_product_price', 'basic', 'The product\'s price.'
    );
    wpseo_register_var_replacement(
    'home', 'get_home_url', 'basic', 'The current language home page.'
    );
}

add_action( 'wpseo_register_extra_replacements', 'register_replacements' );
//option to filter wpseo_register_extra_replacements to get root blog url
function get_seo_home() {
    return get_site_url( 1 );
}

/*
 * used only for SEO
 */
function get_product_price() {
    global $post;
    if ( ! $post ) {
	return;
    }

    //if a woocommerce product always add price and buy link to excerpt
    if ( ($post->post_type == 'product') && ( class_exists( 'woocommerce' ) ) ) {
	$product = wc_get_product( $post );
	if ( $product ) {
	    if ( method_exists( $product, 'get_price' ) ) {
		$price_string = $product->get_price( 'edit' );
		if ( $product->is_on_sale() ) {
		    $price_string = __( 'Sale!', 'woocommerce' ) . $price_string;
		}
		return wp_strip_all_tags( wc_price( $product->get_price() ), true );
	    }
	}
    }
    return '';
}

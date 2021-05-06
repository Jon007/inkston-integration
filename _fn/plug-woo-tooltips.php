<?php

/*
 * provides tooltip wrappers for category and product items in woocommerce catalogue pages
 * automatically hooked, does not require woocommerce template overrides
 *
 * TODO: provide mechanism to turn this on and off, with associated default CSS
 */
/**
 * wrap subcategory thumbnails to show tooltips
 *
 * @param mixed $category
 */
function pre_woocommerce_subcategory_thumbnail( $category ) {
	$title		 = $category->name;
	$description = wp_trim_words( strip_shortcodes( $category->description ), 20 );
	if ( $description ) {
		$title .= ' &#10;' . $description;
	}
	echo( '<span class="tooltip" title="' . $title . '">');
	echo( '<span class="tooltiptext">');
	woocommerce_template_loop_category_title( $category );
	echo( '<span class="imgwrap">');
	woocommerce_subcategory_thumbnail( $category );
	echo( '</span>');
	echo(wp_trim_words( strip_shortcodes( $category->description ), 60 ));
	echo( '</span>');
}

/**
 * close tooltip wrap on subcategory thumbnails
 *
 * @param mixed $category
 */
function post_woocommerce_subcategory_thumbnail( $category ) {
	//<span class="tooltip" opened in pre_woocommerce_subcategory_thumbnail
	echo( '</span>');
}

add_action( 'woocommerce_before_subcategory_title', 'pre_woocommerce_subcategory_thumbnail', 9 );
add_action( 'woocommerce_after_subcategory_title', 'post_woocommerce_subcategory_thumbnail', 11 );

/*
 * get tooltip for products
 *
 * @see woocommerce_template_loop_product_title()
 * @see woocommerce_template_loop_product_thumbnail()
 */
function inkston_product_tooltip() {
	global $post;

	echo( '<span class="tooltip" title="' . get_the_title() . '">');
	echo( '<span class="tooltiptext">');
	woocommerce_template_loop_product_title();
	echo( '<span class="imgwrap">');
	woocommerce_template_loop_product_thumbnail();
	echo( '</span>');
	echo(inkston_get_excerpt( 60 ) . '<br/>');

	$product = wc_get_product( $post );
	//excerpt adds price html so don't repeat it here
	//woocommerce_template_loop_price();
	inkston_product_simple_attributes( $product, array_filter( $product->get_attributes(), 'wc_attributes_array_filter_visible' ), apply_filters( 'wc_product_enable_dimensions_display', $product->has_weight() || $product->has_dimensions() )
	);
	echo( '</span>');
}

add_action( 'woocommerce_before_shop_loop_item', 'inkston_product_tooltip', 20 );

/*
 * close tooltip wrapper after product to avoid spacing caused by closing it before...
 */
function inkston_product_tooltip_close() {
	echo( '</span>');
}

add_action( 'woocommerce_after_shop_loop_item', 'inkston_product_tooltip_close', 50 );

/*
 * get formatted value string for attribute
 *
 * @param WC_Product_Attribute  $attribute
 * @return string   formatted string
 */
function getSimpleAttrValueString( $attribute ) {
	$values			 = array();
	$valuestring	 = '';
	$hasdescription	 = false;
	global $product;

	if ( $attribute->is_taxonomy() ) {
		$attribute_taxonomy	 = $attribute->get_taxonomy_object();
		$attribute_terms	 = wc_get_product_terms( $product->get_id(), $attribute->get_name(), array( 'fields' => 'all' ) );

		foreach ( $attribute_terms as $attribute_term ) {
			$value_name	 = esc_html( $attribute_term->name );
			$values[]	 = $value_name;
		}
	} else {
		$values = $attribute->get_options();

		foreach ( $values as $value ) {
			$value = esc_html( $value );
		}
	}
	$valuestring = wptexturize( implode( ', ', $values ) );
	return apply_filters( 'woocommerce_attribute', $valuestring, $attribute, $values );
}

/*
 * output rows for attribute key-value pairs
 *
 * @param Array     values keyed by display name
 * @return string   formatted string
 */
function outputSimpleAttributes( $attrKeyValues, $type, $variable ) {
	global $product;
	foreach ( $attrKeyValues as $key => $value ) {
		$cellclass = '';
		if ( is_array( $value ) ) {
			$value = implode( ', ', $value );
		}
		if ( $type == 'codes' ) {
			switch ( $key ) {
				case "_sku":
					$cellclass	 = 'woocommerce-variation-custom-' . $key;
					$key		 = 'SKU';
					break;
				default:
					$cellclass	 = 'woocommerce-variation-custom-' . $key;
					$key		 = strtoupper( $key );
			}
		} else {
			switch ( $key ) {
				case "net_weight":
					$cellclass	 = 'woocommerce-variation-custom-' . $key;
					$key		 = __( 'Product Weight', 'inkston-integration' );
					break;
				case "net_size":
					$cellclass	 = 'woocommerce-variation-custom-' . $key;
					$key		 = __( 'Product Size', 'inkston-integration' );
					break;
				case "product_weight":
					$cellclass	 = $key;
					$key		 = __( 'Weight', 'woocommerce' );
					if ( ($value == __( 'N/A', 'woocommerce' )) && ( $product->get_type() == 'variable') ) {
						$value = __( '[depending on variation]', 'inkston-integration' );
					}
					break;
				case "product_dimensions":
					$cellclass	 = $key;
					$key		 = __( 'Dimensions', 'woocommerce' );
					if ( ($value == __( 'N/A', 'woocommerce' )) && ( $product->get_type() == 'variable') ) {
						$value = __( '[depending on variation]', 'inkston-integration' );
					}
					break;
			}
		}
		echo($key . ': ' . $value . '<br />');
		//OR.. output as table
		//echo( '<tr class="'.$type.'"><th>' . $key . '</th> ');
		//echo( ' <td class="' . $cellclass .'">' . $value . '</td></tr>');
	}
}

/*
 * used by inkston_product_tooltip
 */
function inkston_product_simple_attributes( $product, $attributes, $display_dimensions ) {
	/* Product Attributes data structure:
	 * 		'id'        => 0,
	 * 		'name'      => '',
	 * 		'options'   => array(), //array of term ids, see class-wc-product-attribute get_terms, get_slugs
	 * 		'position'  => 0,
	 * 		'visible'   => false,
	 * 		'variation' => false,
	 *
	 */
	global $product;
	$variationattributes = array();
	$archiveattributes	 = array();
	$dimensionattributes = array();
	$otherattributes	 = array();
	$variable			 = ( $product->get_type() == 'variable') ? true : false;


	if ( ! $variable ) {
		if ( $display_dimensions ) {
			if ( $product->has_weight() ) {
				$dimensionattributes[ 'product_weight' ] = esc_html( wc_format_weight( $product->get_weight() ) );
			}
			if ( $product->has_dimensions() ) {
				$dimensionattributes[ 'product_dimensions' ] = esc_html( wc_format_dimensions( $product->get_dimensions( false ) ) );
			}
		}
		$net_weight = get_post_meta( $product->get_id(), 'net_weight', false );
		if ( $net_weight ) {
			if ( is_array( $net_weight ) ) {
				$net_weight							 = recursive_filter_implode( ', ', $net_weight );
				$dimensionattributes[ 'net_weight' ] = $net_weight;
			} else {
				$dimensionattributes[ 'net_weight' ] = esc_html( wc_format_weight( $net_weight ) );
			}
			//in simple view, if there is a net weight, unset the shipping weight
			unset( $dimensionattributes[ 'product_weight' ] );
		}
		$net_size = get_post_meta( $product->get_id(), 'net_size', true );
		if ( $net_size ) {
			$value = esc_html( wc_format_dimensions( $net_size ) );
			if ( $value == __( 'N/A', 'woocommerce' ) ) {
				if ( $product->get_type() == 'variable' ) {
					//$value=__( '[depending on variation]', 'inkston-integration');
					$dimensionattributes[ 'net_size' ] = ''; //$value;
					unset( $dimensionattributes[ 'product_size' ] );
				} else {
					$value = '';
				}
			} else {
				$dimensionattributes[ 'net_size' ] = $value;
				unset( $dimensionattributes[ 'product_size' ] );
			}
		}
	}

	foreach ( $attributes as $attribute ) {
		if ( $attribute->get_visible() ) {
			$name			 = $attribute->get_name();
			$displayname	 = wc_attribute_label( $attribute->get_name() );
			$displayvalue	 = getSimpleAttrValueString( $attribute );
			if ( strpos( strtolower( $name ), 'weight' ) ) {
				$dimensionattributes[ $displayname ] = $displayvalue;
			} elseif ( strpos( strtolower( $name ), 'size' ) ) {
				$dimensionattributes[ $displayname ] = $displayvalue;
			} elseif ( $attribute->get_variation() ) {
				//don't list variation attributes on summary page
				//$variationattributes[$displayname]=$displayvalue;
			} elseif ( strpos( $displayvalue, '<a href' ) ) {
				$archiveattributes[ $displayname ] = $displayvalue;
			} else {
				$otherattributes[ $displayname ] = $displayvalue;
			}
		}
	}
	/* don't need ids in simple view
	  $idfields = array();
	  $idkeys = array( 'asin', '_sku', 'upc');
	  foreach ($idkeys as $key){
	  $value = get_post_meta( $product->get_id(), $key, true );
	  if ($value || $variable){
	  $idfields[$key] = $value;
	  }
	  }
	 */
	ksort( $dimensionattributes );
	//ksort($variationattributes);
	ksort( $archiveattributes );
	ksort( $otherattributes );
	//outputSimpleAttributes($variationattributes, 'variations', false);
	outputSimpleAttributes( $archiveattributes, 'archive-attributes', false );
	outputSimpleAttributes( $otherattributes, 'attributes', false );
	outputSimpleAttributes( $dimensionattributes, 'dimensions', true );
	//outputSimpleAttributes($idfields, 'codes', true);
}

/*
 * enqueue css if this is enabled
 */
inkston_integration::get_instance()->ii_enqueue_css( 'ii-tooltip' );

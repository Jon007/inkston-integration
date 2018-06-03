<?php
/**
 * Product attributes
 *
 * Used by list_attributes() in the products class.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/product-attributes.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see 	    https://docs.woocommerce.com/document/template-structure/
 * @author 		WooThemes
 * @package 	WooCommerce/Templates
 * @version     3.1.0
 * @note        rewritten for inkston: 3.1 change is only make_clickable()
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * get formatted value string for attribute
 *
 * @param WC_Product_Attribute  $attribute
 * @return string   formatted string
 */
if ( ! function_exists( 'getAttrValueString' ) ) {

	function getAttrValueString( $attribute ) {
		$values			 = array();
		$valuestring	 = '';
		$hasdescription	 = false;
		global $product;

		if ( $attribute->is_taxonomy() ) {
			$attribute_taxonomy	 = $attribute->get_taxonomy_object();
			$attribute_terms	 = wc_get_product_terms( $product->get_id(), $attribute->get_name(), array( 'fields' => 'all' ) );

			foreach ( $attribute_terms as $attribute_term ) {
				$value_name = esc_html( $attribute_term->name );

				if ( $attribute_taxonomy->attribute_public ) {
					$link = '<a href="' . esc_url( get_term_link( $attribute_term->term_id, $attribute->get_name() ) ) .
					'" rel="tag">' . $value_name . '</a>';

					$description = $attribute_term->description;  //term_description($attribute_term->term_id, $attribute_taxonomy->name);
					if ( $description ) {
						$hasdescription	 = true;
						$values[]		 = $link . ' ' . $description . '<br/>';
					} else {
						$values[] = $link;
					}
				} else {
					$values[] = $value_name;
				}
			}
		} else {
			$values = $attribute->get_options();

			foreach ( $values as $value ) {
				$value = make_clickable( esc_html( $value ) );
			}
		}
		if ( $attribute->get_variation() || ($hasdescription) ) {
			$valuestring = wpautop( wptexturize( implode( '<br />', $values ) ) );
		} else {
			$valuestring = wpautop( wptexturize( implode( ', ', $values ) ) );
		}
		return apply_filters( 'woocommerce_attribute', $valuestring, $attribute, $values );
	}

}
/*
 * output rows for attribute key-value pairs
 *
 * @param Array     values keyed by display name
 * @return string   formatted string
 */
if ( ! function_exists( 'outputAttributes' ) ) {

	function outputAttributes( $attrKeyValues, $type, $variable ) {
		global $product;
		foreach ( $attrKeyValues as $key => $value ) {
			$cellclass = '';
			if ( is_array( $value ) ) {
				$value = recursive_filter_implode( ', ', $value );
			}
			if ( $type == 'codes' ) {
				switch ( $key ) {
					case "_sku":
						$cellclass	 = 'woocommerce-variation-custom-' . $key;
						$key		 = 'SKU';
						break;
					case "asin":
						$cellclass	 = 'woocommerce-variation-custom-' . $key;
						if ( function_exists( 'ink_amazon_link' ) ) {
							$key	 = 'Amazon EU';
							$value	 = ink_amazon_link( $value, true, false );
						}
						break;
					case "asinusa":
						if ( function_exists( 'ink_amazon_link' ) ) {
							$cellclass	 = 'woocommerce-variation-custom-' . $key;
							$key		 = 'Amazon USA';
							$value		 = ink_amazon_link( $value, false, false );
						}
						break;
					default:
						$cellclass	 = 'woocommerce-variation-custom-' . $key;
						$key		 = strtoupper( $key );
				}
			} else {
				switch ( $key ) {
					case "net_weight":
						$cellclass	 = 'woocommerce-variation-custom-' . $key;
						$key		 = __( 'Product Weight', 'photoline-inkston' );
						break;
					case "net_size":
						$cellclass	 = 'woocommerce-variation-custom-' . $key;
						$key		 = __( 'Product Size', 'photoline-inkston' );
						break;
					case "product_weight":
						$cellclass	 = $key;
						$key		 = __( 'Weight', 'woocommerce' );
						if ( ($value == __( 'N/A', 'woocommerce' )) && ( $product->get_type() == 'variable') ) {
							$value = __( '[depending on variation]', 'photoline-inkston' );
						}
						break;
					case "product_dimensions":
						$cellclass	 = $key;
						$key		 = __( 'Dimensions', 'woocommerce' );
						if ( ($value == __( 'N/A', 'woocommerce' )) && ( $product->get_type() == 'variable') ) {
							$value = __( '[depending on variation]', 'photoline-inkston' );
						}
						break;
				}
			}
			if ( $key ) {
				echo( '<tr class="' . $type . '"><th>' . $key . '</th> ');
				echo( ' <td class="' . $cellclass . '">' . $value . '</td></tr>');
			}
		}
	}

}


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
$productid			 = $product->get_id();


if ( $display_dimensions ) {
	if ( $product->has_weight() ) {
		$dimensionattributes[ 'product_weight' ] = esc_html( wc_format_weight( $product->get_weight() ) );
	}
	if ( $product->has_dimensions() ) {
		$dimensionattributes[ 'product_dimensions' ] = esc_html( wc_format_dimensions( $product->get_dimensions( false ) ) );
	}
}
$net_weight = get_post_meta( $productid, 'net_weight', false );
if ( $net_weight ) {
	if ( is_array( $net_weight ) ) {
		$net_weight							 = recursive_filter_implode( ', ', $net_weight );
		$dimensionattributes[ 'net_weight' ] = $net_weight;
	} else {
		$dimensionattributes[ 'net_weight' ] = esc_html( wc_format_weight( $net_weight ) );
	}
}
$net_size = get_post_meta( $productid, 'net_size', true );
if ( $net_size ) {
	$value = esc_html( wc_format_dimensions( $net_size ) );
	if ( $value == __( 'N/A', 'woocommerce' ) ) {
		if ( $product->get_type() == 'variable' ) {
			//don't add message as may not be set on variations
			//$value=__( '[depending on variation]', 'photoline-inkston');
			$dimensionattributes[ 'net_size' ] = '';
		} else {
			$value = '';
		}
	} else {
		$dimensionattributes[ 'net_size' ] = $value;
	}
}

foreach ( $attributes as $attribute ) {
	if ( $attribute->get_visible() ) {
		$name			 = $attribute->get_name();
		$displayname	 = wc_attribute_label( $attribute->get_name() );
		$displayvalue	 = getAttrValueString( $attribute );
		if ( strpos( strtolower( $name ), 'weight' ) ) {
			$dimensionattributes[ $displayname ] = $displayvalue;
		} elseif ( strpos( strtolower( $name ), 'size' ) ) {
			$dimensionattributes[ $displayname ] = $displayvalue;
		} elseif ( $attribute->get_variation() ) {
			$variationattributes[ $displayname ] = $displayvalue;
		} elseif ( strpos( $displayvalue, '<a href' ) ) {
			$archiveattributes[ $displayname ] = $displayvalue;
		} else {
			$otherattributes[ $displayname ] = $displayvalue;
		}
	}
}

if ( $product->get_type() == 'simple' ) {
	//add shipping class note
	$shippingclassid	 = $product->get_shipping_class_id();
	$shippingclassname	 = __( 'Shipping', 'photoline-inkston' );
	$shippingclasstext	 = '';
	$shippinglink		 = get_permalink( pll_get_post( 7420 ) );
	if ( $product->get_price() > inkston_free_shipping_level() ) {
		$shippingclassname	 .= ': ' . __( 'Free', 'photoline-inkston' );
		$shippingclasstext	 = __( 'Order including this product will qualify for free shipping.', 'photoline-inkston' );
	} else {
		if ( $shippingclassid ) {
			$term				 = get_term_by( 'id', $shippingclassid, 'product_shipping_class' );
			$shippingclasstext	 = '<a href="' . $shippinglink . '">' . $term->name . '</a><br />'
			. $term->description;
		} else {
			$shippingclassname	 .= ': ' . __( 'Standard', 'photoline-inkston' );
			$shippingclasstext	 = '<a href="' . $shippinglink . '">'
			. __( 'Standard shipping.', 'photoline-inkston' ) . '</a>';
		}
	}
	$dimensionattributes[ $shippingclassname ] = $shippingclasstext;
}

$idfields	 = array();
$idkeys		 = array( 'asin', 'asinusa', '_sku', 'upc' );
foreach ( $idkeys as $key ) {
	$value = get_post_meta( $productid, $key, true );
	if ( $value || $variable ) {
		$idfields[ $key ] = $value;
	}
}
if ( isset( $idfields[ 'asin' ] ) && is_array( $idfields[ 'asin' ] ) ) {
	$idfields[ 'asin' ] = array_filter( $idfields[ 'asin' ] );
	if ( implode( '', $idfields[ 'asin' ] ) == '' ) {
		unset( $idfields[ 'asin' ] );
	}
}
if ( ! isset( $idfields[ 'asinusa' ] ) || ! ($idfields[ 'asinusa' ]) ) {
	if ( isset( $idfields[ 'asin' ] ) ) {
		$idfields[ 'asinusa' ] = $idfields[ 'asin' ];
	}
}
if ( isset( $idfields[ 'asinusa' ] ) ) {
	if ( is_array( $idfields[ 'asinusa' ] ) ) {
		$idfields[ 'asinusa' ] = array_filter( $idfields[ 'asinusa' ] );
		if ( implode( '', $idfields[ 'asinusa' ] ) == '' ) {
			unset( $idfields[ 'asinusa' ] );
		}
	} elseif ( ($idfields[ 'asinusa' ] == 'NONE') || ! ($idfields[ 'asinusa' ]) ) {
		unset( $idfields[ 'asinusa' ] );
	}
}
//TODO: TEMP LINE WHILE AMAZON EU ISSUE
//unset( $idfields[ 'asin' ] );
ksort( $idfields );
?>
<table class="shop_attributes">
	<?php
	ksort( $dimensionattributes );
	ksort( $variationattributes );
	ksort( $archiveattributes );
	ksort( $otherattributes );
	outputAttributes( $variationattributes, 'variations', false );
	outputAttributes( $archiveattributes, 'archive-attributes', false );
	outputAttributes( $otherattributes, 'attributes', false );
	outputAttributes( $dimensionattributes, 'dimensions', true );
	outputAttributes( $idfields, 'codes', true );
	?>
</table>
<?php

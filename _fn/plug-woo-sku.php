<?php

/*
 * Inkston SKU generator
 */

/*
 * if sku option is turned on, and a format is set
 * and is not a translated product
 * then return sku format to be applied
 * (otherwise return empty string and no processing will be done)
 */
function skuformat() {
	static $skuformat	 = '';  //eg 'ink-{initials}-{id}'
	static $donecheck	 = false;

	//options check is done once
	if ( ! $donecheck ) {
		$ii_options	 = ii_get_options();
		$do_sku		 = ( isset( $ii_options[ 'sku' ] ) ) ? $ii_options[ 'sku' ] : false;
		if ( $do_sku ) {
			if ( isset( $ii_options[ 'skuformat' ] ) ) {
				$skuformat = $ii_options[ 'skuformat' ];
			}
		}
		$donecheck = true;
	}

	return $skuformat;
}

//check once if sku generation is on and a format is set, if so, setup hooks
if ( skuformat() ) {
	if ( is_ajax() ) {
		// generate SKUs when variations are saved via ajax
		add_action( 'woocommerce_ajax_save_product_variations', 'ajax_maybe_generate_variation_skus', 10, 1 );
		add_action( 'woocommerce_save_product_variation', 'ink_maybe_generate_variation_sku', 10, 2 );
	} elseif ( is_admin() ) {

		//set the sku after first save of new product if still blank
		//(if sku includes product it, may not be able to create sku until save)
		//would like to set the sku before save (if product already has an id but no sku)
		//but this gets called much too often and would only apply in certain circumstances...
		//add_action( 'woocommerce_before_product_object_save', 'ink_maybe_generate_sku_before_product_object_save', 100, 2 );
		// save the generated SKUs during product edit / bulk edit
		add_action( 'woocommerce_product_bulk_edit_save', 'ink_maybe_generate_and_save_sku', 10, 1 );
		add_action( 'woocommerce_process_product_meta', 'ink_maybe_generate_and_save_sku', 100, 2 );

		//add_action( 'woocommerce_new_product', 'ink_maybe_generate_and_save_sku', 100, 1 );
		//add_action( 'woocommerce_update_product', 'ink_maybe_generate_and_save_sku', 100, 1 );

		add_action( 'woocommerce_save_product_variation', 'ink_maybe_generate_variation_sku', 10, 2 );

		add_action( 'woocommerce_product_duplicate_before_save', 'ink_product_duplicate_before_save', 10, 2 );

		//add_filter( 'woocommerce_duplicate_product_exclude_meta', 'ink_duplicate_product_exclude_meta', 10, 1 );
		add_action( 'woocommerce_duplicate_product', 'ink_new_product_sku_on_duplicate', PHP_INT_MAX, 2 );
	}
}
function post_is_translation( $post ) {
	//in multilingual environment only create language in base ccy
	if ( function_exists( 'pll_current_language' ) ) {
		//we actually need to test the product language not the current language
		//$curlang = pll_current_language();
		$post_id = ( is_numeric( $post ) ) ? $post : $post->id;
		$curlang = pll_get_post_language( $post_id );
		$deflang = pll_default_language();
		if ( $curlang !== $deflang ) {
			//actually if it is a translation we could skip all processing since the
			//translation tool should synchronise sku automatically
			return true;
		} else {
			return false;
		}
	}
}

/*
 * WooCommerce product_duplicate adds a "-n" suffix to sku of duplicated product
 * here we remove it so it can be generated using the rules here
 */
function ink_product_duplicate_before_save( $duplicate, $product ) {
	$duplicate->set_sku( '' );
}

/*
 * after duplicating product, set the sku
 */
function ink_new_product_sku_on_duplicate( $duplicate, $product ) {
	ink_maybe_generate_and_save_sku( $duplicate );
}

/*
 * add sku to the list of attributes to be ignored when duplicating..
 * this approach doesn't work because sku is explicitly set by product_duplicate
 * and is not treated as meta and not deleted from the meta of the cloned product
  function ink_duplicate_product_exclude_meta( $excludes ) {
  $excludes[] = '_sku';
  $excludes[] = 'sku';
  return $excludes;
  }
 */

/*
 * On save of product, if there is no sku set, set the sku
 * - not used
 *
  function ink_maybe_generate_sku_before_product_object_save( $product, $data_store ) {
  ink_maybe_generate_and_save_sku( $product );
  }
 */
/**
 * maybe generate variation sku on
 * woocommerce_save_product_variation action
 *
 * @param int $product product variation ID
 * @param int $i   product variation index
 *
 * @return boolean false if something went wrong
 */
function ink_maybe_generate_variation_sku( $product, $i ) {
	// Checks to ensure we have a product and gets one if we have an ID
	if ( is_numeric( $product ) ) {
		$product = wc_get_product( absint( $product ) );
	}
	//$variationslug = ink_get_formatted_variation_slug( $product );
	$parent = $product->get_parent_id();
	maybe_save_variation_sku( $product, $parent );
}

/**
 * if it's a valid product with no sku,
 *  generate sku and update the product
 *
 * @param \WC_Product|int $product WC_Product object or product ID
 * @return string sku
 */
function ink_maybe_generate_and_save_sku( $product ) {

	// Checks to ensure we have a product and gets one if we have an ID
	if ( ! $product ) {
		return;
	}
	if ( is_numeric( $product ) ) {
		$product = wc_get_product( absint( $product ) );
	}
	//if the product has no id yet and we need id for the sku, bail
	if ( ! $product->get_id() && strpos( skuformat(), '{id}' ) ) {
		return '';
	}
	//no generation for translations,
	//will be generated in base language and copied to translation
	if ( post_is_translation( $product->get_id() ) ) {
		return '';
	}

	$current_sku = $product->get_sku();
	if ( ! $current_sku || $current_sku == '' ) {
		// Generate the SKU for simple / external / variable parent products
		$current_sku = generate_product_sku( $product );
		// save the SKU for simple / external / parent products if we should
		$product->set_sku( $current_sku );
		$product->save();
	}

	// Only generate / save variation SKUs when we should
	if ( $product->is_type( 'variable' ) ) {
		$variations = $product->get_children();
		foreach ( $variations as $variation_id ) {
			maybe_save_variation_sku( $variation_id, $product, $current_sku );
		}
	}

	return $current_sku;
}

/*
 * build sku according to the skuformat configured in plugin options
 *
 * @param \WC_Product|int $product WC_Product object or product ID
 */
function generate_product_sku( $product ) {
	if ( ! $product ) {
		return;
	}
	$product = wc_get_product( $product );

	//if format includes initials, get initials from product title and substitute them
	$newsku = skuformat();
	if ( strpos( $newsku, '{initials}' ) ) {
		$initials	 = ink_initials( $product->get_title() );
		$newsku		 = str_replace( '{initials}', $initials, $newsku );
	}
	$newsku	 = str_replace( '{id}', $product->get_id(), $newsku );
	$newsku	 = ensure_unique_sku( $product->get_id(), $newsku );
	return $newsku;
}

/**
 * Maybe save variation SKU -- used when updating variations via ajax.
 *
 * @param int $variation_id the variation ID
 * @param \WC_Product $parent the parent product object
 * @param string $parent_sku if being generated, the new parent SKU
 */
function maybe_save_variation_sku( $variation_id, $parent, $parent_sku = null ) {
	$parent = wc_get_product( $parent );
	if ( ! $parent ) {
		return;
	}

	if ( post_is_translation( $parent->get_id() ) ) {
		return;
	}
	$variation = wc_get_product( $variation_id );
	if ( ! $variation ) {
		return;
	}
	$parent_sku = $parent_sku ? $parent_sku : $parent->get_sku();
	if ( $variation->is_type( 'variation' ) && ( ! empty( $parent_sku ) ) ) {
		$variation_slug = ink_get_formatted_variation_slug( $variation, true );
		if ( $variation_slug != '' ) {
			$newname = $parent->get_name() . '-' . $variation_slug;
			$variation->set_name( $newname );
		}
		$oldsku = $variation->get_sku();
		if ( $oldsku == '' || $oldsku == $parent_sku ) {
			if ( $parent_sku == '' ) {
				$parent_sku = ink_maybe_generate_and_save_sku( $parent );
			}
			$variation_slug = ink_get_formatted_variation_slug( $variation, false );
			if ( $variation_slug && $parent_sku ) {
				$sku = ensure_unique_sku( $variation->get_id(), $parent_sku . '-' . $variation_slug );
				$variation->set_sku( $sku );
				$variation->save();
			}
		}
	}
}

/**
 * Maybe generate variation SKUs on ajax save.
 *
 * @param int $parent_id the parent product ID
 */
function ajax_maybe_generate_variation_skus( $parent_id ) {

	$product = wc_get_product( $parent_id );
	if ( ! $product ) {
		return;
	}


	if ( $product instanceof WC_Product && $product->is_type( 'variable' ) && 'never' !== get_option( 'wc_sku_generator_variation' ) ) {

		$variations = $product->get_children();

		foreach ( $variations as $variation_id ) {
			maybe_save_variation_sku( $variation_id, $product );
		}
	}
}

/*
 * Generates a string of initials from an input string
 *
 * @param string $input string to process for initials
 *
 * @return string processed string of initials
 */
function ink_initials( $input ) {
	//replace non-ascii to avoid odd results making initials for titles like:
	//"famous ancient chinese painting album wang lu %e7%8e%8b%e5%b1%a5 landscape"
	$tmp	 = str_replace( array( '-', '_' ), ' ', sanitize_title( $input, '', 'save' ) );
	$tmp	 = preg_replace( '/[^\x20-\x7E]/', '', $tmp );
	$words	 = explode( ' ', $tmp );
	$output	 = '';
	foreach ( $words as $word ) {
		if ( $word ) {
			//get the first letter, in upper case
			$letter	 = substr( $word, 0, 1 );
			$letter	 = strtoupper( $letter );
			//if ascii value is outside the range A-Z, use Z
			$asc	 = ord( $letter );
			if ( $asc < 65 || $asc > 90 ) {
				$letter = 'Z';
			}
			$output .= $letter;
		}
	}
	return ($output) ? $output : $input;
}

/**
 * simplified variant of wc_get_formatted_variation for nicer variation slug or sku
 *
 * Gets a formatted version of variation data or item meta.
 *
 * @param array|WC_Product_Variation $variation Variation object.
 * @param bool $is_name is for variation name (in which case attributes in name omitted)
 * @return string
 */
function ink_get_formatted_variation_slug( $variation, $is_name ) {
	if ( ! $variation ) {
		return '';
	}

	$return = '';

	if ( is_a( $variation, 'WC_Product_Variation' ) ) {
		$variation_attributes	 = $variation->get_attributes();
		$product				 = $variation;
		$variation_name			 = $variation->get_name();
	} else {
		$product				 = false;
		$variation_name			 = '';
// Remove attribute_ prefix from names.
		$variation_attributes	 = array();
		if ( is_array( $variation ) ) {
			foreach ( $variation as $key => $value ) {
				$variation_attributes[ str_replace( 'attribute_', '', $key ) ] = $value;
			}
		}
	}


	if ( is_array( $variation_attributes ) ) {

		$variation_list = array();

		foreach ( $variation_attributes as $name => $value ) {
			// If this is a term slug, get the term's slug.
			if ( taxonomy_exists( $name ) ) {
				$term = get_term_by( 'slug', $value, $name );
				if ( ! is_wp_error( $term ) && ! empty( $term->slug ) ) {
					$value = $term->slug;
				}
			}

			// Do not list attributes already part of the variation name.
			if ( $is_name && ('' === $value || ( wc_is_attribute_in_product_name( $value, $variation_name ) ) ) ) {
				continue;
			}
			$attrslug = rawurldecode( $value );
			if ( ! $is_name ) {
				$attrslug	 = preg_replace( '/[^A-Za-z0-9\-]/', '', $attrslug );
				$attrslug	 = str_replace( '-', '', $attrslug );
				if ( strlen( $attrslug ) > 4 ) {
					$attrslug = strtoupper( substr( $attrslug, 0, 4 ) );
				}
			}
			$variation_list[] = $attrslug;
		}
		if ( $is_name ) {
			$return .= implode( ', ', $variation_list );
		} else {
			$return .= implode( '-', $variation_list );
		}
	}
	return $return;
}

/*
 * ensure a unique sku is generated:
 * if it is not unique, add letters to the end until it is.
 * (should never happen with products if id included, could happen with variations)
 * Note woocommerce does have a wc_product_generate_unique_sku() function
 * which adds numbers to the end..  though as the default sku here
 *
 * @param int    $product_id Product ID.
 * @param string $sku Product SKU.
 * @return string validated unique sku
 */
function ensure_unique_sku( $product_id, $sku ) {
	if ( wc_product_has_unique_sku( $product_id, $sku ) ) {
		return $sku;
	} else {
		//random upper case letter to be added to the end
		$letter = chr( rand( 65, 90 ) );
		return ensure_unique_sku( $product_id, $sku . $letter );
	}
}

<?php
/*
 * adds custom fields for:
 *  asin
 *  upc
 *  net size and net weight
 * for both products and variations
 * Automatically hooked
 */


/**
 * MODIFY woocommerce_single_product_summary hook.
 * add inkston product attribute display in main product info area and suppress tabs and additional title
 *
 * @hooked woocommerce_template_single_title - 5
 * @hooked woocommerce_template_single_rating - 10
 * @hooked woocommerce_template_single_price - 10
 * @hooked woocommerce_template_single_excerpt - 20
 * @hooked woocommerce_template_single_add_to_cart - 30
 * @hooked woocommerce_template_single_meta - 40
 * @hooked woocommerce_template_single_sharing - 50
 */
//add_action( 'woocommerce_single_product_summary', 'inkston_display_product_attributes', 45 );
remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 20 );

/**
 * Hook: woocommerce_after_single_product_summary.
 *
 * @hooked woocommerce_output_product_data_tabs - 10
 * @hooked woocommerce_upsell_display - 15
 * @hooked woocommerce_output_related_products - 20
 */
add_action( 'woocommerce_after_single_product_summary', 'woocommerce_template_single_excerpt', 4 );
add_action( 'woocommerce_after_single_product_summary', 'inkston_display_product_attributes', 5 );
remove_action( 'woocommerce_product_additional_information', 'wc_display_product_attributes', 10 );
add_action( 'woocommerce_after_single_product_summary', 'inkston_output_description', 12 );


add_filter( 'woocommerce_product_additional_information_heading', 'inkston_suppress_additional_information_heading', 10, 1 );
add_filter( 'woocommerce_product_tabs', 'inkston_suppress_additional_info_tab' );
function inkston_output_description() {
//	wc_get_template( 'single-product/tabs/description.php' );
	the_content();
}

/*
 *
 */
function inkston_suppress_additional_info_tab( $tabs ) {
	unset( $tabs[ 'additional_information' ] );
	unset( $tabs[ 'description' ] );
	return $tabs;
}

/*
 *
 */
function inkston_display_product_attributes() {
	global $product;
	wc_display_product_attributes( $product );
}

/*
 *
 */
function inkston_suppress_additional_information_heading( $product ) {
	return false;
}

/*
 * Create a default Product Attribute object for the supplied name
 *
 * @param  string   name        Product Attribute taxonomy name
 *
 * @return WC_Product_Attribute/bool  new Attribute or false if named Attribute is not found
 *
 */
function inkston_make_product_attribute( $name ) {
	global $wc_product_attributes;
	if ( isset( $wc_product_attributes[ $name ] ) ) {
		$newattr = new WC_Product_Attribute();
		$newattr->set_id( 1 );  //any positive value is interpreted as is_taxonomy=true
		$newattr->set_name( $name );
		$newattr->set_visible( true );
		$newattr->set_variation( false );
		//example of setting default value for item
		if ( $name == 'pa_brand' ) {
			$term = get_term_by( 'slug', 'inkston', $name );
			if ( $term ) {
				$newattr->set_options( array( $term->term_id ) );
			}
		}
		return $newattr;
	} else {
		return false;
	}
}

/*
 * Add default attributes to a product
 */
function inkston_default_product_attributes() {
	global $product;
	if ( ! $product ) {
		$product = $GLOBALS[ 'product_object' ];
	}
	if ( ! $product ) {
		return;
	}
	$attributes = $product->get_attributes();

	$defaultAttributes = array(
		'pa_brand',
		'pa_maker',
		'pa_materials',
		//        'pa_asin',
		//        'pa_upc',
		'pa_packaging',
		'pa_recommend-to',
		'pa_suitable-for',
	//        'pa_product-size',
	//        'pa_net-weight',
	);

	$changed = false;
	foreach ( $defaultAttributes as $key ) {
		if ( ! isset( $attributes[ $key ] ) ) {
			$newattr = inkston_make_product_attribute( $key );
			if ( $newattr ) {
				$attributes[ $key ] = $newattr;
			}
			$changed = true;
		}
	}
	if ( $changed ) {
		$product->set_attributes( $attributes );
	}
}

/*
 * added to last hook before rendering of Product Edit screen
 */
add_action( 'woocommerce_product_write_panel_tabs', 'inkston_default_product_attributes' );


/*
 * Save custom fields
 *
 * @param int   $post_id    product id
 * @param object $post      the product
 */
function inkston_meta_save( $post_id, $post ) {
	inkston_meta_save_item( $post_id, 'asin', null );
	inkston_meta_save_item( $post_id, 'asinusa', null );
	inkston_meta_save_item( $post_id, 'upc', null );
	inkston_meta_save_item( $post_id, 'net_weight', null );
	$netsize = array( esc_attr( $_POST[ '_netlength' ] ), esc_attr( $_POST[ '_netwidth' ] ), esc_attr( $_POST[ '_netheight' ] ) );
	inkston_meta_save_item( $post_id, 'net_size', $netsize );
}

add_action( 'woocommerce_process_product_meta', 'inkston_meta_save', 10, 2 );

/*
 * Save individual custom field
 *
 * @param int   $post_id    product id
 * @param object $key       parameter name
 */
function inkston_meta_save_item( $post_id, $key, $value ) {
	if ( empty( $value ) ) {
		if ( isset( $_POST[ $key ] ) ) {
			$value = $_POST[ $key ];
		}
	}
	if ( ! empty( $value ) ) {
		update_post_meta( $post_id, $key, $value );
	}
}

// Save Variation Settings
function inkston_save_variation_meta( $variation_id, $i ) {
	inkston_variation_meta_save_item( $variation_id, $i, 'asin' );
	inkston_variation_meta_save_item( $variation_id, $i, 'asinusa' );
	inkston_variation_meta_save_item( $variation_id, $i, 'upc' );
	inkston_variation_meta_save_item( $variation_id, $i, 'net_weight' );
	inkston_variation_meta_save_item( $variation_id, $i, 'net_size' );
}

add_action( 'woocommerce_save_product_variation', 'inkston_save_variation_meta', 10, 2 );

/*
 * Save individual custom field
 *
 * @param int   $post_id    product id
 * @param object $key       parameter name
 */
function inkston_variation_meta_save_item( $variation_id, $key, $fieldname ) {
	$value = null;
	if ( isset( $_POST[ $fieldname ][ $key ] ) ) {
		$value = $_POST[ $fieldname ][ $key ];
		update_post_meta( $variation_id, $fieldname, $value );
	} else {
		delete_post_meta( $variation_id, $fieldname );
	}
}

/**
 * load custom fields for variations
 *
 */
function inkston_load_variation_settings_fields( $variations ) {

	// duplicate the line for each field
	$variations[ 'asin' ]		 = get_post_meta( $variations[ 'variation_id' ], 'asin', true );
	$variations[ 'asinusa' ]	 = get_post_meta( $variations[ 'variation_id' ], 'asinusa', true );
	$variations[ 'upc' ]		 = get_post_meta( $variations[ 'variation_id' ], 'upc', true );
	$variations[ 'net_weight' ]	 = get_post_meta( $variations[ 'variation_id' ], 'net_weight', true );
	$variations[ 'net_size' ]	 = get_post_meta( $variations[ 'variation_id' ], 'net_size', true );

	return $variations;
}

// Add New Variation Settings
add_filter( 'woocommerce_available_variation', 'inkston_load_variation_settings_fields' );




/*
 * add ASIN and UPC fields directly to the Inventory tab underneath SKU
 */
function inkston_add_asin_upc() {
	global $thepostid, $post;
	$thepostid = empty( $thepostid ) ? $post->ID : $thepostid;

	$value = get_post_meta( $thepostid, 'asin', true );
	if ( is_array( $value ) ) {
		$value = reset( $value );
	}

	woocommerce_wp_text_input(
	array(
		'id'			 => 'asin',
		'label'			 => __( 'ASIN EU', 'inkston-integration' ),
		'placeholder'	 => 'A01MA02ZON',
		'desc_tip'		 => 'true',
		'value'			 => $value,
		'description'	 => __( 'Amazon alphanumeric 10 character inventory code.', 'woocommerce' )
	)
	);

	$value = get_post_meta( $thepostid, 'asinusa', true );
	if ( is_array( $value ) ) {
		$value = reset( $value );
	}
	woocommerce_wp_text_input(
	array(
		'id'			 => 'asinusa',
		'label'			 => __( 'ASIN USA', 'inkston-integration' ),
		'placeholder'	 => 'A01MA02ZON',
		'desc_tip'		 => 'true',
		'value'			 => $value,
		'description'	 => __( 'Amazon USA code if different from EU code. Use NONE if not available in USA.', 'woocommerce' )
	)
	);

	$value = get_post_meta( $thepostid, 'upc', true );
	if ( is_array( $value ) ) {
		$value = implode( ', ', $value );
	}

	woocommerce_wp_text_input(
	array(
		'id'				 => 'upc',
		'label'				 => __( 'UPC', 'inkston-integration' ),
		'placeholder'		 => '012345678901',
		'desc_tip'			 => 'true',
		'value'				 => $value,
		'description'		 => __( '12 digits international standard Universal Product Code.', 'inkston-integration' ),
		'type'				 => 'number',
		'custom_attributes'	 => array(
			'step'	 => 'any',
			'min'	 => '0'
		)
	)
	);
}

add_action( 'woocommerce_product_options_sku', 'inkston_add_asin_upc' );
function inkston_net_dimensions() {
	global $thepostid, $post;
	$thepostid = empty( $thepostid ) ? $post->ID : $thepostid;

	$value = get_post_meta( $thepostid, 'net_weight', true );
	if ( is_array( $value ) ) {
		$value = implode( ', ', $value );
	}

	woocommerce_wp_text_input(
	array(
		'id'			 => 'net_weight',
		'label'			 => __( 'Product weight', 'inkston-integration' ) .
		' ( ' . get_option( 'woocommerce_weight_unit' ) . ')',
		'placeholder'	 => __( 'Unpacked net product weight.', 'inkston-integration' ),
		'desc_tip'		 => 'true',
		'description'	 => __( 'Unpacked net product weight.', 'inkston-integration' ),
		'value'			 => $value,
	/*
	 * numeric type doesn't handle variation with multiple values..
	  'type'              => 'number',
	  'custom_attributes' => array(
	  'step' 	=> 'any',
	  'min'	=> '0'
	  )
	 *
	 */
	)
	);
	// net size, copying structure of Shipping size
	global $product;
	if ( ! $product ) {
		$product = $GLOBALS[ 'product_object' ];
	}
	if ( ! $product ) {
		return;
	}
	$net_size = get_post_meta( $product->get_id(), 'net_size', true );
	if ( ! $net_size ) {
		$net_size = array( '', '', '' );
	}
	?>
	<p class="form-field dimensions_field net_size">
		<label for="Product Size"><?php
			echo __( 'Product size ', 'inkston-integration' ) .
			' ( ' . get_option( 'woocommerce_dimension_unit' ) . ')';
			?></label>
		<span class="wrap">
			<input id="net_length" placeholder="<?php esc_attr_e( 'Length', 'woocommerce' ); ?>" class="input-text wc_input_decimal" size="6" type="text" name="_netlength" value="<?php echo esc_attr( wc_format_localized_decimal( $net_size[ 0 ] ) ); ?>" />
			<input placeholder="<?php esc_attr_e( 'Width', 'woocommerce' ); ?>" class="input-text wc_input_decimal" size="6" type="text" name="_netwidth" value="<?php echo esc_attr( wc_format_localized_decimal( $net_size[ 1 ] ) ); ?>" />
			<input placeholder="<?php esc_attr_e( 'Height', 'woocommerce' ); ?>" class="input-text wc_input_decimal last" size="6" type="text" name="_netheight" value="<?php echo esc_attr( wc_format_localized_decimal( $net_size[ 2 ] ) ); ?>" />
		</span>
		<?php echo wc_help_tip( __( 'Unpacked product size LxWxH in decimal form', 'inkston-integration' ) ); ?>
	</p><?php
}

add_action( 'woocommerce_product_options_dimensions', 'inkston_net_dimensions' );

/*
 *  Add Variation Settings
 */
function inkston_variation_asin_upc( $loop, $variation_data, $variation ) {
	$value = get_post_meta( $variation->ID, 'asin', true );
	woocommerce_wp_text_input(
	array(
		'id'			 => 'asin[' . $loop . ']',
		'label'			 => __( 'ASIN EU', 'inkston-integration' ),
		'placeholder'	 => 'A01MA02ZON',
		'desc_tip'		 => 'true',
		'description'	 => __( 'Amazon alphanumeric 10 character inventory code.', 'woocommerce' ),
		'value'			 => get_post_meta( $variation->ID, 'asin', true ),
		'wrapper_class'	 => 'form-row form-row-first'
	)
	);

	$value = get_post_meta( $variation->ID, 'asinusa', true );
	woocommerce_wp_text_input(
	array(
		'id'			 => 'asinusa[' . $loop . ']',
		'label'			 => __( 'ASIN USA', 'inkston-integration' ),
		'placeholder'	 => 'A01MA02ZON',
		'desc_tip'		 => 'true',
		'description'	 => __( 'Amazon USA code if different from EU code..', 'woocommerce' ),
		'value'			 => get_post_meta( $variation->ID, 'asinusa', true ),
		'wrapper_class'	 => 'form-row form-row-first'
	)
	);

	woocommerce_wp_text_input(
	array(
		'id'				 => 'upc[' . $loop . ']',
		'label'				 => __( 'UPC', 'inkston-integration' ),
		'placeholder'		 => '012345678901',
		'desc_tip'			 => 'true',
		'description'		 => __( 'Unpacked product size LxWxH in decimal form', 'inkston-integration' ),
		'type'				 => 'number',
		'custom_attributes'	 => array(
			'step'	 => 'any',
			'min'	 => '0'
		),
		'value'				 => get_post_meta( $variation->ID, 'upc', true ),
		'wrapper_class'		 => 'form-row form-row-last'
	)
	);
}

add_action( 'woocommerce_variation_options', 'inkston_variation_asin_upc', 10, 3 );

/*
 *  Add Variation Settings for net dimensions
 */
function inkston_variation_net_dimensions( $loop, $variation_data, $variation ) {
	$value = get_post_meta( $variation->ID, 'net_weight', true );
	woocommerce_wp_text_input(
	array(
		'id'				 => 'net_weight[' . $loop . ']',
		'label'				 => __( 'Product weight', 'inkston-integration' ) .
		' ( ' . get_option( 'woocommerce_weight_unit' ) . ')',
		'placeholder'		 => __( 'Unpacked net product weight.', 'inkston-integration' ),
		'desc_tip'			 => 'true',
		'description'		 => __( 'Unpacked net product weight.', 'inkston-integration' ),
		'type'				 => 'number',
		'custom_attributes'	 => array(
			'step'	 => 'any',
			'min'	 => '0'
		),
		'value'				 => get_post_meta( $variation->ID, 'net_weight', true ),
		'wrapper_class'		 => 'form-row form-row-first'
	)
	);

	$value = get_post_meta( $variation->ID, 'net_size', true );
	woocommerce_wp_text_input(
	array(
		'id'			 => 'net_size[' . $loop . ']',
		'label'			 => __( 'Product size ', 'inkston-integration' ) .
		' ( ' . get_option( 'woocommerce_dimension_unit' ) . ')',
		'placeholder'	 => '0x0x0',
		'desc_tip'		 => 'true',
		'description'	 => __( 'Unpacked product size LxWxH in decimal form', 'inkston-integration' ),
		'value'			 => get_post_meta( $variation->ID, 'net_size', true ),
		'wrapper_class'	 => 'form-row form-row-last'
	)
	);
}

add_action( 'woocommerce_variation_options_dimensions', 'inkston_variation_net_dimensions', 10, 3 );


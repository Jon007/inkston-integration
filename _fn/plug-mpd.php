<?php

/*
 * Extend Multisite Post Duplicator to better support WooCommerce cross-listings
 */

/*
 * Terms which are woocommerce categories have thumbnails
 * 		copy these when copying category to another site
 * Todo: consider ither possible meta:
 * 		order - may not make sense in destination depending what terms already copied
 * 		display_type - ok to leave default or let target site decide
 *
 * @param array $new_term array('term_id' => $term_id, 'term_taxonomy_id' => $taxid)
 *    as previously returned by wp_insert_term
 * @param int $old_term The Term object for the old term
 * @param int $source_blog_id The ID of the source blog
 * @param int $target_blog_id The ID of target blog
 */
function ink_add_cat_thumbnail( $new_term, $old_term, $source_blog_id ) {
	//could use a before filter and woo function create_product_category
	// - this uses update_woocommerce_term_meta internally
	//woopoly also uses update_woocommerce_term_meta which uses update_term_meta
	//update_woocommerce_term_meta is simply a wrapper handling earlier wp versions
	//where update_term_meta does not exist.
	//Get the ID of the target blog - at this point it is the current blog
	$target_blog_id	 = get_current_blog_id();
	switch_to_blog( $source_blog_id );
	$imgid			 = get_term_meta( $old_term->term_id, 'thumbnail_id', true );
	if ( $imgid ) {

		$image = wp_get_attachment_image_src( $imgid, 'full' );
		if ( $image ) {
			$image_details	 = array(
				'id'			 => $imgid,
				'url'			 => get_attached_file( $imgid ),
				'alt'			 => get_post_meta( $imgid, '_wp_attachment_image_alt', true ),
				'post_title'	 => get_post_field( 'post_title', $imgid ),
				'description'	 => get_post_field( 'post_content', $imgid ),
				'caption'		 => get_post_field( 'post_excerpt', $imgid ),
				'post_name'		 => get_post_field( 'post_name', $imgid )
			);
			//may create attachment post with 0 as parent as there is no parent post
			$new_image_id	 = ink_copy_image_to_destination( 0, $image_details, $source_blog_id, $target_blog_id );
			if ( $new_image_id ) {
        //save the product gallery meta to the new post id in destination blog
        switch_to_blog( $target_blog_id );
        update_term_meta( $new_term[ 'term_id' ], 'thumbnail_id', $new_image_id );
        return true;
      }
    }
	}
	switch_to_blog( $target_blog_id );
}

add_action( 'mpd_after_insert_term', 'ink_add_cat_thumbnail', 10, 3 );

/* add persist links on bulk copy:
 *
 * @param int $source_post_id The ID of the source post
 * @param int $dest_post_id The ID of target post
 * @param int $source_blog_id The ID of the source blog
 * @param int $target_blog_id The ID of target blog
 *
 */
function ink_mpd_batch_add_links( $source_post_id, $source_blog_id, $destination_post_id, $destination_blog_id ) {

	//get options from source or destination blog?
	$options = get_blog_option( $source_blog_id, 'mdp_settings' );
	//$options = get_option( 'mdp_settings' );
	if ( ! $options || ( ! isset( $options[ 'allow_persist' ] ) ) ) {
		return;
	}

	/*
	 * 		'source_id' : The ID of the source site
	 * 		'destination_id' : The ID of the destination site
	 * 		'source_post_id' : The ID of the source post that was copied
	 * 		'destination_post_id' : The ID of the source post that was copied
	 */
	$args = array(
		'source_id'				 => $source_blog_id,
		'destination_id'		 => $destination_blog_id,
		'source_post_id'		 => $source_post_id,
		'destination_post_id'	 => $destination_post_id
	);
	//if the post is already duplicated to target blog, update
	//otherwise, add a new persistence link
	//(only add the link here as we are running on mpd_single_batch_after
	// when the copy has already been done..)
	if ( ! ink_handle_already_copied_post( $args ) ) {
		mpd_add_persist( $args );
	}
}

add_action( 'mpd_single_batch_after', 'ink_mpd_batch_add_links', 10, 4 );
/**
 * if a post is already copied, [update it if option applies and]
 * return true to avoid further processing
 *
 * @param $args Array
 * 		Required Params
 * 		'source_id' : The ID of the source site
 * 		'destination_id' : The ID of the destination site
 * 		'source_post_id' : The ID of the source post that was copied
 * 		'destination_post_id' : the ID of the destination post
 * @return int destination post id if already copied to destination blog or zero
 */
function ink_handle_already_copied_post( $args ) {

	global $wpdb;

	$tableName = $wpdb->base_prefix . "mpd_log";

	$query = $wpdb->prepare( "SELECT *
				FROM $tableName
				WHERE
				source_id = %d
				AND destination_id = %d
				AND source_post_id = %d
				AND destination_post_id = %d", $args[ 'source_id' ], $args[ 'destination_id' ], $args[ 'source_post_id' ], $args[ 'destination_post_id' ] );

	$persist_post = $wpdb->get_row( $query );

	if ( $persist_post ) {
		//if duplicated without persistence option, return early without doing any update
		if ( ! $persist_post->persist_active ) {
			return $args[ 'destination_post_id' ];
		}

		//logically copied from mpd_get_persists_for_post and mpd_persist_post
		//for maximum compatibility with core code path
		$wanted_post_statuses	 = array_keys( mpd_get_post_statuses() );
		$the_post				 = get_blog_post( $persist_post->destination_id, $persist_post->destination_post_id );
		//if the destination post no longer exists or isn't in the right status, just return the id
		if ( ! $the_post || ! in_array( $the_post->post_status, $wanted_post_statuses ) ) {
			return $persist_post->destination_post_id;
		}

		$args = apply_filters( 'mpd_persist_post_args', array(
			'source_id'				 => intval( $persist_post->source_id ),
			'destination_id'		 => intval( $persist_post->destination_id ),
			'source_post_id'		 => intval( $persist_post->source_post_id ),
			'destination_post_id'	 => intval( $persist_post->destination_post_id )
		) );
		//mpd_persist_post_args filter has the option to add 'skip_normal_persist'
		//to abort further processing
		if ( array_key_exists( 'skip_normal_persist', $args ) ) {
			return $args[ 'destination_post_id' ];
		}

		//actually do the update
		mpd_persist_over_multisite( $persist_post );

		// Increment the count
		mpd_set_persist_count( $args );

		do_action( 'mpd_after_persist', $args );

		return $persist_post->destination_post_id;
	} else {
		//no link, return false
		return false;
	}
}

/*
 * copy image attachments from _product_image_gallery meta, similar processing
 * as thumbnail/featured image, updating the meta in the new post with the new value
 *
 * @param int $post_id The ID of destination post in destination blog
 * @param int $mdp_post invalid parameter (filtered post data used to create post in destination blog
 * 											but not valid to pass array as action parameter)
 * @param int $source_blog_id The ID of the source blog
 * @param int $source_post_id The ID of the source variable product
 *
 * @return bool true if image gallery found and processed
 */
function ink_mpd_copy_product_image_gallery( $post_id, $mdp_post, $source_blog_id, $source_post_id ) {
	//Get the ID of the target blog - at this point it is the current blog
	$target_blog_id = get_current_blog_id();

	//get the _product_image_gallery from the source post in the source blog
	switch_to_blog( $source_blog_id );
	$product_image_gallery	 = get_post_meta( $source_post_id, '_product_image_gallery', true );
	$new_product_gallery	 = [];

	//if found, copy all images to target
	if ( $product_image_gallery ) {
		$product_images = explode( ',', $product_image_gallery );
		foreach ( $product_images as $imgid ) {
			$image = wp_get_attachment_image_src( $imgid, 'full' );
			if ( $image ) {
				$image_details = array(
					'id'			 => $imgid,
					'url'			 => get_attached_file( $imgid ),
					'alt'			 => get_post_meta( $imgid, '_wp_attachment_image_alt', true ),
					'post_title'	 => get_post_field( 'post_title', $imgid ),
					'description'	 => get_post_field( 'post_content', $imgid ),
					'caption'		 => get_post_field( 'post_excerpt', $imgid ),
					'post_name'		 => get_post_field( 'post_name', $imgid )
				);

				$new_product_gallery[] = ink_copy_image_to_destination( $post_id, $image_details, $source_blog_id, $target_blog_id );
			}
		}

		//save the product gallery meta to the new post id in destination blog
		switch_to_blog( $target_blog_id );
		update_post_meta( $post_id, '_product_image_gallery', implode( ',', $new_product_gallery ) );
		return true;
	} else {
		switch_to_blog( $target_blog_id );
		return false;
	}
}

//product gallery copy could be rewritten to hook mpd_during_core_in_source
add_action( 'mpd_end_of_core_before_return', 'ink_mpd_copy_product_image_gallery', 10, 4 );

//on update additional check should be done to avoid re-copying images which are already copied
//should be handled already by mpd_does_file_exist
add_action( 'mpd_persist_end_of_core_before_return', 'ink_mpd_copy_product_image_gallery', 10, 4 );
/**
 * This function performs the generic action of copying an image to the destination blog
 * Note: adapted from mpd function mpd_set_featured_image_to_destination(),
 * 	removing the featured image part to allow generic use for other fields.
 *  anything odd here, see: mpd_set_featured_image_to_destination
 *
 * @param int $destination_id The ID of the newly created post
 * @param array $image_details details of image to be copied.
 * @param int $source_blog_id The ID of the source blog
 * @param int $target_blog_id The ID of target blog
 *
 * @return attachment id  (post id of new image post)
 *
 */
function ink_copy_image_to_destination( $destination_id, $image_details, $source_blog_id, $target_blog_id ) {
	$previous_blog = get_current_blog_id();
	switch_to_blog( $target_blog_id );

	// Get the upload directory for the current site
	$upload_dir	 = wp_upload_dir();
	// Get all the data inside a file and attach it to a variable
	// Get the file name of the source file
	$filename	 = basename( $image_details[ 'url' ] );

	// Make the path to the desired path to the new file we are about to create
	if ( wp_mkdir_p( $upload_dir[ 'path' ] ) ) {
		$file = $upload_dir[ 'path' ] . '/' . $filename;
	} else {

		$file = $upload_dir[ 'basedir' ] . '/' . $filename;
	}

	// Add the file contents to the new path with the new filename
	if ( $the_original_id = mpd_does_file_exist( $image_details[ 'id' ], $source_blog_id, $target_blog_id ) ) {

		// Get the mime type of the new file extension
		$wp_filetype = wp_check_filetype( $filename, null );

		$attachment = array(
			'ID'			 => $the_original_id,
			'post_parent'	 => $destination_id,
			'post_mime_type' => $wp_filetype[ 'type' ],
			'post_title'	 => $image_details[ 'post_title' ],
			'post_content'	 => $image_details[ 'description' ],
			'post_status'	 => 'inherit',
			'post_excerpt'	 => $image_details[ 'caption' ],
			'post_name'		 => $image_details[ 'post_name' ]
		);

		$attach_id = wp_insert_attachment( $attachment );

		// Include code to process functions below:
		require_once(ABSPATH . 'wp-admin/includes/image.php');

		// Define attachment metadata
		$attach_data = wp_generate_attachment_metadata( $attach_id, $file );

		// Assign metadata to attachment
		wp_update_attachment_metadata( $attach_id, $attach_data );
	} else {

		if ( $image_details[ 'url' ] && $file ) {
			//copy could commonly fail, so try to ensure clean exit..
			try {
			  copy( $image_details[ 'url' ], $file );
			} catch ( Exception $e ) {
				error_log( 'Unable to copy "' . $image_details[ 'url' ] . '" due to exception: ' . $e->getMessage() );
				switch_to_blog( $previous_blog );
				return false;
			}
		}

		// Get the mime type of the new file extension
		$wp_filetype	 = wp_check_filetype( $filename, null );
		// Get the URL (not the URI) of the new file
		$new_file_url	 = $upload_dir[ 'url' ] . '/' . $filename;

		// Create the database information for this new image
		$attachment = array(
			'post_mime_type' => $wp_filetype[ 'type' ],
			'post_title'	 => $image_details[ 'post_title' ],
			'post_content'	 => $image_details[ 'description' ],
			'post_status'	 => 'inherit',
			'post_excerpt'	 => $image_details[ 'caption' ],
			'post_name'		 => $image_details[ 'post_name' ]
		);

		// Attach the new file and its information to the database
		$attach_id = wp_insert_attachment( $attachment, $file, $destination_id );

		// Add alt text from the destination image
		if ( $image_details[ 'alt' ] ) {

			update_post_meta( $attach_id, '_wp_attachment_image_alt', $image_details[ 'alt' ] );
		}

		// Include code to process functions below:
		require_once(ABSPATH . 'wp-admin/includes/image.php');

		// Define attachment metadata
		$attach_data = wp_generate_attachment_metadata( $attach_id, $file );

		// Assign metadata to attachment
		wp_update_attachment_metadata( $attach_id, $attach_data );
	}
	switch_to_blog( $previous_blog );
	return $attach_id;
}

/*
 * Check for and duplicate/update variations of a variable product..
 *
 * Note: function is called for all posts so needs to exit fast if not in fact a variable product
 * Note: blog on entry to this function is expected to be the source blog
 * Note, existing varations in the target which are not included in the udpate are left unchanged.
 *   This means variations deleted from the source may still exist in the target.
 *   This might be valid if they are still in stock / on sale in the target and only withdrawn from the source
 *
 *
 * @param int $source_post_id The ID of the source variable product
 * @param int $dest_post_id The ID of target variable product
 * @param int $source_blog_id The ID of the source blog
 * @param int $target_blog_id The ID of target blog
 *
 * @return bool true if processed, false if not processed
 */
function ink_duplicate_variations( $source_post_id, $dest_post_id, $source_blog_id, $target_blog_id ) {
	ini_set( 'max_execution_time', 900 );

	$current_blog = get_current_blog_id();
	switch_to_blog( $source_blog_id );

	$source_post = get_post( $source_post_id );
	if ( ! $source_post || ($source_post->post_type != 'product') ) {
		return false;
	}

	if ( ! class_exists( 'woocommerce' ) ) {
		error_log( 'Warning: woocommerce not active, unable to copy variations for variable product ' .
		$source_post_id . ' blog:' . $source_blog_id );
		return false;
	}

	/* could bypass the woocommerce api here: benefit is partly the wootransient cache,
	 * see class-wc-product-variable-data-store-cpt.php readchildren() */
	$source_product = wc_get_product( $source_post_id );
	if ( ! $source_product || 'variable' !== $source_product->get_type() ) {
		return false;
	}
	//returns array of variation ids
	$from_variations = $source_product->get_available_variations();

	foreach ( $from_variations as $from_variation ) {
		//read meta from source before switching blogs
		//(filter later depending on whether it will be new or updated target variation)
		$from_variation_id					 = $from_variation[ 'variation_id' ];
		$from_variation_post				 = get_post( $from_variation_id );
		$meta_values						 = get_post_meta( $from_variation_id );
		$featured_image						 = mpd_get_featured_image_from_source( $from_variation_id );
		$meta_values[ '_source_variation' ]	 = $from_variation_id;
		/*
		 * First we check if the "to" product contains the duplicate meta
		 * key to find out if we have to update or insert
		 */
		switch_to_blog( $target_blog_id );
		$posts								 = get_posts( array(
			'meta_key'		 => '_source_variation',
			'meta_value'	 => $from_variation_id,
			'post_type'		 => 'product_variation',
			'post_parent'	 => $dest_post_id,
		) );
		$dest_variation_id					 = 0;
		switch ( count( $posts ) ) {
			case 1:  // if update add meta filtered by persist filter
				$dest_variation_id					 = $posts[ 0 ]->ID;
				mpd_process_meta( $dest_variation_id, apply_filters( 'mpd_filter_persist_post_meta', $meta_values
				, $from_variation_id, $dest_variation_id, $source_blog_id, $target_blog_id ) );
				break;
			case 0:  // insert
				//get the post data to insert from source post
				//should pass $mpd_process_info to filter but thats not necessarily available here so recreating it:
				$mpd_process_info					 = array(
					'source_post_id'		 => $source_post_id,
					'destination_id'		 => $target_blog_id,
					'post_type'				 => 'product_variation',
					'post_author'			 => $from_variation_post->post_author,
					'prefix'				 => '',
					'requested_post_status'	 => $from_variation_post->post_status
				);
				$from_variation_post				 = ( array ) $from_variation_post;
				//variation post name includes parent post..
				$from_variation_post[ 'post_name' ]	 = str_replace( $source_post_id, $dest_post_id, $from_variation_post[ 'post_name' ] );
				$data								 = apply_filters( 'mpd_setup_destination_data', ( array ) $from_variation_post, $mpd_process_info );
				//remove the id as will be inserted into destination blog with new id
				unset( $data[ 'ID' ] );
				//the parent is the parent variable product in the destination blog
				$data[ 'post_parent' ]				 = $dest_post_id;
				//inserted as a standard post, avoiding recursive calls
				ink_avoid_duplicate_calls( false );
				$dest_variation_id					 = wp_insert_post( $data );
				//add meta filtered by standard mpd copy filter
				mpd_process_meta( $dest_variation_id, apply_filters( 'mpd_filter_post_meta', $meta_values
				, $from_variation_id, $dest_variation_id, $source_blog_id, $target_blog_id ) );
				break;
			default:
				// we can not handle , something wrong here
				//TODO: actually handle this, extra variations previously created in error
				error_log( 'ERROR: Duplicate variations detected, please check and delete extra variations ' . implode( ',', $default_meta ) . ' from product ' . $dest_post_id );
				break;
		}
		if ( $featured_image && $dest_variation_id ) {
			//Check that the users plugin settings actually want this process to happen
			$options = get_option( 'mdp_settings' );
			if ( (isset( $options[ 'mdp_default_featured_image' ] ) || ! $options) && apply_filters( 'mdp_default_featured_image', true ) ) {

				mpd_set_featured_image_to_destination( $dest_variation_id, $featured_image, $source_blog_id );
			}
		}

		switch_to_blog( $source_blog_id );
	}
	switch_to_blog( $current_blog );
	return true;
}

/*
 * rationalise parameters and call duplicate_variations.
 * Note that an update post may have new variations so the function needs to handle both new and update
 *
 * @param int $post_id The ID of target variable product
 * @param int $mdp_post invalid parameter (filtered post data used to create post in destination blog
 * 											but not valid to pass array as action parameter)
 * @param int $source_blog_id The ID of the source blog
 * @param int $source_post_id The ID of the source variable product
 *
 */
function ink_mpd_create_or_update_variations( $post_id, $mdp_post, $source_blog_id, $source_post_id ) {
	ink_duplicate_variations( $source_post_id, $post_id, $source_blog_id, get_current_blog_id() );
}

/* add variations for new products */
add_action( 'mpd_end_of_core_before_return', 'ink_mpd_create_or_update_variations', 20, 4 );
/* ideally, also update variations for existing products */
add_action( 'mpd_persist_end_of_core_before_return', 'ink_mpd_create_or_update_variations', 20, 4 );

/*
 * filter post meta to be copied.
 * Note: as each meta is a separate insert in wordpress, there is performance benefit in skipping all unnecessary meta
 * 		 so testing skipping all dubious and empty meta
 *
 * @param array $post_meta array of meta values to filter.
 * @param int $source_post_id The ID of the source  product
 * @param int $dest_post_id The ID of target product (may be zero for new product not created yet)
 * @param int $source_blog_id The ID of the source blog
 * @param int $target_blog_id The ID of target blog
 *
 * @return array filtered post meta
 */
function ink_filter_mpd_meta( $post_meta, $source_post_id, $dest_post_id, $source_blog_id, $target_blog_id, $is_first_time ) {

	//set up currency conversion or site inflation factor for converting prices
	//we need the specific options for the destination site, not the source site
	$current_blog	 = get_current_blog_id();
	switch_to_blog( $target_blog_id );
	$blog_ii_options = get_option( 'ii_options' );
	switch_to_blog( $current_blog );
	$sitepricefactor = ( isset( $blog_ii_options[ 'sitepricefactor' ] ) && is_numeric( $blog_ii_options[ 'sitepricefactor' ] ) ) ? $blog_ii_options[ 'sitepricefactor' ] : 1;
	$sitepricedecimals	 = get_option( 'woocommerce_price_num_decimals', 2 );
	$sitepricesync	 = isset( $blog_ii_options[ 'sitepricesync' ] );
	$sitesaleesync	 = isset( $blog_ii_options[ 'sitesalesync' ] );

	$filtered_meta = [];
	$blank_meta		 = [];
	$default_meta	 = [];
	foreach ( $post_meta as $metakey => $metavalue ) {
		switch ( $metakey ) {
			//meta we can't reasonably process
			case '_upsell_ids':
			case '_crosssell_ids':
			case '_children':
				//related product fields such as upsell and cross-sell will have to be managed directly on child site
				//as the related products from parent site may not exist yet on the child site
				break;
			//unused download meta
			case '_downloadable':
			case '_downloadable_files':
			case '_download_limit':
			case '_download_expiry':
			case '_download_type':
			//let these variation price summary be recalculated
			case '_min_variation_price':
			case '_max_variation_price':
			case '_min_price_variation_id':
			case '_max_price_variation_id':
			case '_min_variation_regular_price':
			case '_max_variation_regular_price':
			case '_min_regular_price_variation_id':
			case '_max_regular_price_variation_id':
			case '_min_variation_sale_price':
			case '_max_variation_sale_price':
			case '_min_sale_price_variation_id':
			case '_max_sale_price_variation_id':
			//other meta we know not to copy
			case '_thumbnail_id':  //handled separately via this plugin
			case '_point_to_variation': //handled separately via this plugin
			case '_product_image_gallery': //converted by this plugin in later hook
			case '_translation_porduct_type': //woopoly meta
			case '_product_version':
			case '_edit_lock':
			case '_edit_last':
			case '_no_shipping_required':
			case '_paypal_billing_agreement':
			case '_featured,_enable_sandbox_mode':
			case '_enable_ec_button':
			case '_visibility':
			case '_the_champ_meta':
			case '_msrp_price':
			case '_enable_sandbox_mode':
			case 'subscribed_user_ids':
			case '_wp_old_slug':
				break;
			//asin is specific to vendor so not copied
			case 'asin':
			case '_wpla_asin':
			case 'asinusa':
				break;
			//prices needing conversion
			case '_msrp':
			case '_price':
			case '_regular_price':
				if ( $is_first_time || $sitepricesync ) {
					if ( is_numeric( $metavalue[ 0 ] ) ) {
						$filtered_meta[ $metakey ] = array( round( $metavalue[ 0 ] * $sitepricefactor, $sitepricedecimals ) );
					}
				}
				break;
			//special case, sale price will be copied, and only converted on first use
			case '_sale_price':
				if ( $is_first_time || $sitesaleesync ) {
					if ( is_numeric( $metavalue [ 0 ] ) ) {
						$filtered_meta[ $metakey ] = array( round( $metavalue[ 0 ] * $sitepricefactor, $sitepricedecimals ) );
					}
				}
				break;
			case '_sale_price_dates_from':
			case '_sale_price_dates_to':
				if ( $sitesaleesync ) {
					$filtered_meta[ $metakey ] = $metavalue;
				}
			//rating data from source site not really valid but maybe an indication on first copy
			case '_wc_average_rating':
			case '_wc_rating_count':
			case '_wc_review_count':
			//values only copied for first time creation of new posts, not synchronized
			case 'menu_order':
			case 'comment_status':
			case '_tax_class':
			case 'total_sales':
			case '_backorders':
			case '_sold_individually':
			case '_stock_status':
				if ( $is_first_time ) {
					$filtered_meta[ $metakey ] = $metavalue;
				}
				break;
			//special case, manage stock will be forced to true on creation only
			case '_manage_stock':
				if ( $is_first_time ) {
					$filtered_meta[ $metakey ] = 'yes';
				}
				break;
			//special case, stock itself will be set to zero, on creation only
			case '_stock':
				if ( $is_first_time ) {
					$filtered_meta[ $metakey ] = 0;
				}
				break;
			case '_tax_status':
				if ( $is_first_time ) {
					$filtered_meta[ $metakey ] = 'taxable';
				}
				break;
			//meta we know we want!
			case '_source_variation': //handled (added) by this plugin for variations
			case '_featured':
			case '_weight':
			case 'net_weight':
			case '_length':
			case '_width':
			case '_height':
			case 'net_size':
			case 'product-type':
			case '_virtual':
			case '_sku':
			case 'upc':
			case '_variation_description':
			case '_purchase_note':
			case 'attribute_size':
			case '_product_attributes':
			case '_default_attributes':
				$filtered_meta[ $metakey ] = $metavalue;
				break;
			default:
				//generally don't copy amazon, ebay and vendor specific meta between sites
				if ( strpos( $metakey, '_stcr' ) !== false ) {
					//subscribe to comments reloaded should be per-site
				} elseif ( strpos( $metakey, 'amazon' ) !== false ) {
					//amazon meta different for each vendor
				} elseif ( strpos( $metakey, 'ebay' ) !== false ) {
					//ebay meta different for each vendor
				} elseif ( strpos( $metakey, 'snap' ) !== false ) {
					//social media publication meta
				} elseif ( strpos( $metakey, 'yoast' ) !== false ) {

				} elseif ( strpos( $metakey, 'oasis' ) !== false ) {

				} elseif ( strpos( $metakey, 'wpla' ) !== false ) {

				} elseif ( strpos( $metakey, 'woosb_' ) !== false ) {

				} elseif ( strpos( $metakey, '_oembed_' ) !== false ) {

				} else {
					if ( $metavalue ) {
						if (
						(
						(is_array( $metavalue )) &&
						(isset( $metavalue[ 0 ] )) &&
						($metavalue[ 0 ])
						) || ($metavalue) ) {
							//TODO: we could do an extra check that the metavalue isn't an array of 1 blank item and not copy it..
							//everything else ok
							$filtered_meta[ $metakey ]	 = $metavalue;
							//don't treat custom product level attributes as unknown
							if ( strpos( $metakey, 'attribute_' ) == false ) {
                $default_meta[]				 = $metakey;
              }
            }
          }
      }
    }
	}
	if ( count( $default_meta ) ) {
		error_log( 'The following meta have no special handling and were copied by default: ' . implode( ',', $default_meta ) );
	}
	return $filtered_meta;
}

/*
 * Hook mpd meta filters and route to ink_filter function
 *
 * @param array $post_meta array of meta values to filter.
 * @param int $dest_post_id The ID of the newly created post
 * @param int $source_blog_id The ID of the source blog
 * @param int $target_blog_id The ID of target blog
 *
 */
function ink_filter_mpd_meta_new( $post_meta, $source_post_id, $dest_post_id, $source_blog_id, $target_blog_id ) {
	return ink_filter_mpd_meta( $post_meta, $source_post_id, $dest_post_id, $source_blog_id, $target_blog_id, true );
}

function ink_filter_mpd_meta_update( $post_meta, $source_post_id, $dest_post_id, $source_blog_id, $target_blog_id ) {
	return ink_filter_mpd_meta( $post_meta, $source_post_id, $dest_post_id, $source_blog_id, $target_blog_id, false );
}

add_filter( 'mpd_filter_post_meta', 'ink_filter_mpd_meta_new', 10, 5 );
add_filter( 'mpd_filter_persist_post_meta', 'ink_filter_mpd_meta_update', 10, 5 );
function ink_avoid_duplicate_calls( $mpd_process_info ) {
	add_filter( 'mpd_persist_post_args', 'skip_duplicate_calls', 10, 1 );
}

add_filter( 'mpd_before_core', 'ink_avoid_duplicate_calls', 10, 1 );
function skip_duplicate_calls( $args ) {
	$args[ 'skip_normal_persist' ] = true;
	remove_filter( 'mpd_persist_post_args', 'skip_duplicate_calls', 10 );
	return $args;
}

/*
 * remove terms not wanted in site duplication such as post_translations, language
 * also many attributes only used for a few products so avoid creating unnecessary
 * blank attribute values where not used
 *
 * @param array $source_taxonomy_terms_object array of $tax => &$tax_data
 * @param int $destination_id The ID of target blog
 */
function ink_skip_terms( $source_taxonomy_terms_object, $destination_id ) {
	foreach ( $source_taxonomy_terms_object as $tax => &$tax_data ) {
		switch ( $tax ) {
			case 'language':
			case 'post_translations':
				unset( $source_taxonomy_terms_object[ $tax ] );
				break;
			default:
				if (
				( ! is_array( $tax_data ) ) ||
				(count( $tax_data ) == 0) ||
				( ! ( $tax_data[ 0 ] ) )
				) {
					unset( $source_taxonomy_terms_object[ $tax ] );
				}
		}
	}

	return $source_taxonomy_terms_object;
}

add_filter( 'mpd_post_taxonomy_terms', 'ink_skip_terms', 10, 2 );

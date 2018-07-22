<?php

/*
 * Extend Multisite Post Duplicator to better support WooCommerce cross-listings
 */


/* JM: add persist links on bulk copy: can't currently implement here as
 *    mpd_add_persist requires arguments below
 *    but mpd_batch_after only passes $results */
//function ink_mpd_batch_add_links( $results ) {
//
//	/*
//	 * 		'source_id' : The ID of the source site
//	 * 		'destination_id' : The ID of the destination site
//	 * 		'source_post_id' : The ID of the source post that was copied
//	 * 		'destination_post_id' : The ID of the source post that was copied
//	 */
//	$args = array(
//		'source_id'				 => get_current_blog_id(),
//		'destination_id'		 => $get_site[ 0 ],
//		'source_post_id'		 => $post_id,
//		'destination_post_id'	 => $results[ $highest_index ][ 'id' ]
//	);
//	mpd_add_persist( $args );
//}
//
//add_action( 'mpd_batch_after', 'ink_mpd_batch_add_links', 10, 1 );


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

			copy( $image_details[ 'url' ], $file );
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
	switch_to_blog( $source_blog_id );
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
				$dest_variation_id					 = $posts[ 0 ];
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
				//inserted as a standard post
				$dest_variation_id					 = wp_insert_post( $data );
				//add meta filtered by standard mpd copy filter
				mpd_process_meta( $dest_variation_id, apply_filters( 'mpd_filter_post_meta', $meta_values
				, $from_variation_id, $dest_variation_id, $source_blog_id, $target_blog_id ) );
				break;
			default:
				// we can not handle , something wrong here
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
 * filter post meta to be copied
 *
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
	$sitepricesync	 = isset( $blog_ii_options[ 'sitepricesync' ] );
	$sitesaleesync	 = isset( $blog_ii_options[ 'sitesalesync' ] );

	$filtered_meta = [];
	foreach ( $post_meta as $metakey => $metavalue ) {
		switch ( $metakey ) {
			//meta we can't reasonably process
			case '_upsell_ids':
			case '_crosssell_ids':
			case '_children':
				//related product fields such as upsell and cross-sell will have to be managed directly on child site
				//as the related products from parent site may not exist yet on the child site
				break;
			//other meta we know not to copy
			case '_product_image_gallery': //converted by this plugin in later hook
			case '_translation_porduct_type':
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
						$filtered_meta[ $metakey ] = array( $metavalue[ 0 ] * $sitepricefactor );
					}
				}
				break;
			//special case, sale price will be copied, and only converted on first use
			case '_sale_price':
				if ( $is_first_time || $sitesaleesync ) {
					if ( is_numeric( $metavalue [ 0 ] ) ) {
						$filtered_meta[ $metakey ] = array( $metavalue[ 0 ] * $sitepricefactor );
					}
				}
				break;
			case '_sale_price_dates_from':
			case '_sale_price_dates_to':
				if ( $sitesaleesync ) {
					$filtered_meta[ $metakey ] = $metavalue;
				}
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
			case '_default_attributes':
				$filtered_meta[ $metakey ] = $metavalue;
				break;
			default:
				//generally don't copy amazon, ebay and vendor specific meta between sites
				if ( strpos( '_stcr', $metakey ) !== false ) {
					//subscribe to comments reloaded should be per-site
				} elseif ( strpos( 'amazon', $metakey ) !== false ) {
					//amazon meta different for each vendor
				} elseif ( strpos( 'ebay', $metakey ) !== false ) {
					//ebay meta different for each vendor
				} elseif ( strpos( 'snap', $metakey ) !== false ) {
					//social media publication meta
				} else {
					//everything else ok
					$filtered_meta[ $metakey ] = $metavalue;
				}
		}
	}
	return $filtered_meta;
}

/* not specifically handled, as unused
  '_downloadable',
  '_downloadable_files',
  '_download_limit',
  '_download_expiry',
  '_download_type',
 */

/*
 * Hook mpd meta filters and route to ink_filter function
 */
function ink_filter_mpd_meta_new( $post_meta, $source_post_id, $dest_post_id, $source_blog_id, $target_blog_id ) {
	return ink_filter_mpd_meta( $post_meta, $source_post_id, $dest_post_id, $source_blog_id, $target_blog_id, true );
}

function ink_filter_mpd_meta_update( $post_meta, $source_post_id, $dest_post_id, $source_blog_id, $target_blog_id ) {
	return ink_filter_mpd_meta( $post_meta, $source_post_id, $dest_post_id, $source_blog_id, $target_blog_id, false );
}

add_filter( 'mpd_filter_post_meta', 'ink_filter_mpd_meta_new', 10, 5 );
add_filter( 'mpd_filter_persist_post_meta', 'ink_filter_mpd_meta_update', 10, 5 );

<?php
/*
 * Image file handling
 * TODO: move medium-large image setup to photoswipe
 */


/*
 * get theme logo, if available
 */
function get_logo() {
	$custom_logo_id = get_theme_mod( 'custom_logo' );
	if ( $custom_logo_id ) {
		$image = wp_get_attachment_image_src( $custom_logo_id, 'full' );
		return $image[ 0 ];
	} elseif ( is_inkston() ) {
		return network_site_url( 'logo.png' );
	} elseif ( get_theme_mod( 'logo_upload' ) ) {
		$logo = str_replace( array( 'http:', 'https:' ), '', get_theme_mod( 'logo_upload' ) );
	} elseif ( file_exists( ABSPATH . 'logo.png' ) ) {
		return network_site_url( 'logo.png' );
	} else {
		error_log( 'warning, logo not set for this theme and no default logo.png found' );
		return 'https://www.inkston.com/logo.png';
	}
}

/*
 * get default image when no image is available
 * TODO: make this a theme setting?
 */
function get_noimage() {
	if ( file_exists( ABSPATH . 'no-image.png' ) ) {
		return network_site_url( 'no-image.png' );
	} else {
		error_log( 'warning, logo not set for this theme and no default logo.png found' );
		return 'https://www.inkston.com/no-image.png';
	}
}

/*
 * get default image for forums
 */
function get_forum_noimage() {
	if ( file_exists( ABSPATH . 'forum.jpg' ) ) {
		return network_site_url( 'forum.jpg' );
	} else {
		return get_noimage();
	}
}

/**
 * Add titles to images - called from media.php wp_get_attachment_image
 *
 * @author Bill Erickson
 * @link https://github.com/billerickson/display-posts-shortcode/issues/109
 *
 * @param array $attr, image attributes
 * @param object $attachment, post object for the image
 * @param string $size, image size
 * @return array $attr
 */
function be_add_title_to_images( $attr, $attachment, $size ) {
	$title = '';
	if ( ! isset( $attr[ 'title' ] ) ) {
		$title			 = get_the_title( $attachment->post_parent );
		$attr[ 'title' ] = $title;
	}
	return $attr;
}

add_filter( 'wp_get_attachment_image_attributes', 'be_add_title_to_images', 10, 3 );


/*
 *  Register image sizes for use in Add Media modal
 */
function ink_custom_sizes( $sizes ) {
	return array_merge( $sizes, array(
		'medium_large' => __( 'Medium large' ),
	) );
}

add_filter( 'image_size_names_choose', 'ink_custom_sizes' );
/*
 * ensure medium-large image is registered
 */
function add_medium_large() {
	add_image_size( 'medium-large', 600, 600 );
}

add_action( 'init', 'add_medium_large' );
/**
 * Extracting the first image of post as fallback if no featured image
 */
function inkston_catch_image( $post_id = 0 ) {
	global $post;
	$thisPost	 = ($post_id) ? get_post( $post_id ) : $post;
	$first_img = '';
	if ( $thisPost ) {
	if ( function_exists( 'bbp_is_single_user' ) && bbp_is_single_user() ) {
		$first_img = inkston_featured_img_tag( get_avatar( bbp_get_displayed_user_field( 'user_email', 'raw' ) ), false );
		} elseif ( is_single() || ($thisPost && $thisPost->ID) ) {
			$first_img = inkston_featured_img_tag( $thisPost->post_content, false );
		}
	}
	if ( empty( $first_img ) ) {
		$first_img = get_noimage();
	}
	return $first_img;
}

/**
 * Filter: 'wpseo_pre_analysis_post_content' - Allow filtering the content before analysis
 *
 * @param string $content - post content
 * @param WP_Post $post - post
 * @param bool $tag - return tag or url
 *
 * @return string image
 */
function inkston_featured_img_tag( $content, $returntag, $fallback = true, $forum_id = 0 ) {
	$first_img	 = '';
	$last_avatar = '';

	if ( $fallback ) {
		//check if we are on a bbPress forum post
		global $post;
		if ( $post ) {
			switch ( $post->post_type ) {
				case 'topic':
					$forum_id = bbp_get_topic_forum_id( $post->ID );

					/*
					 * first, specifically check the current reply:
					 * - so if the current link is one reply in the thread,
					 * meta info will get the reply specific picture if any
					 */
					global $wp;
					if ( isset( $wp->query_vars[ 'page' ] ) ) {
            $pageid = $wp->query_vars[ 'page' ];
            if ( $pageid ) {
              $replypost = get_post( $pageid );
              if ( $replypost ) {
                $replyimg = inkston_featured_img_tag( $replypost->post_content, false, false, $forum_id );
                if ( $replyimg ) {
                  $first_img	 = $replyimg;
                  $content	 = '';
                  break;
                }
              }
            }
					}

					//then check all the replies from latest
					$replies = bbp_get_public_child_ids( $post->ID, bbp_get_reply_post_type() );
					foreach ( $replies as $reply ) {
						$replypost	 = bbp_get_reply( $reply );
						$replyimg	 = inkston_featured_img_tag( $replypost->post_content, false, false, $forum_id );
						if ( $replyimg ) {
							$first_img	 = $replyimg;
							$content	 = '';
							break;
						}
					}
					break;
				case 'reply':
					$forum_id = bbp_get_reply_forum_id( $post->ID );
					break;
				default:
			}
		}
	}

	if ( $content ) {
		try {
			$doc		 = new DOMDocument();
			libxml_use_internal_errors( true );
			$doc->loadHTML( $content );
			$imageTags	 = $doc->getElementsByTagName( 'img' );
			libxml_clear_errors();

			/*
			 * NOTE: this gets the image sized as on the page, size not guaranteed,
			 * may also get an external image so no guarantee thumbnail is available  */
			foreach ( $imageTags as $imgtag ) {
				//$url = ($returntag) ? $imgtag->ownerDocument->saveXML( $imgtag ) : $imgtag->getAttribute( 'src' );
				$url = $imgtag->getAttribute( 'src' );
				if ( (strpos( $url, 'cat-generator-avatars' ) === false) && (strpos( $url, 'badge' ) === false) && (strpos( $url, 'avatar' ) === false) ) {
					$first_img = $url;
					//for forums, continue to last image, otherwise get first non-avatar image
					if ( ! $forum_id ) {
						break;
					}
				} else {
					$last_avatar = $url;
				}
			}
		} catch ( Exception $e ) {
			//if the input isn't fully valid html, try regex
			if ( $first_img == '' && $last_avatar == '' ) {
				return inkston_featured_img_tag_regex( $content, false, false, $forum_id );
			}
		}
	}

	if ( empty( $first_img ) && $fallback ) {
		if ( is_archive() ) {
			$first_img = ($forum_id ) ? get_forum_noimage() : get_noimage();
		} elseif ( $last_avatar ) {
			$first_img = $last_avatar;
		} elseif ( $forum_id ) { //last chance check for bbPress
			$thumbnail = wp_get_attachment_image_src( get_post_thumbnail_id( $forum_id ), 'medium' );
			if ( $thumbnail ) {
				$first_img = $thumbnail[ 0 ];
			}
		}
		if ( empty( $first_img ) ) {
			$first_img = get_noimage();
		}
	}
	if ( $returntag && $first_img ) {
		$first_img = '<img src="' . $first_img . '" />';
	}
	return $first_img;
}

/*
 * this regex didn't quite cope with the wide variety of inputs
 */
function inkston_featured_img_tag_regex( $content, $tag, $fallback = true, $forum_id = 0 ) {
	$first_img	 = '';
	$last_avatar = '';
	$output		 = preg_match_all( '/<img.+src=[\'"]([^\'"]+)[\'"].*>/i', $content, $matches );
	/* $output = preg_match_all("/<img\s[^>]*?src\s*=\s*['\"]([^'\"]*?)['\"][^>]*?>", $content, $matches); */
	//$output = preg_match_all( '/<img.+src=[\'"]([^\'"]+)[\'"].*>/i', $content, $matches);
	//check if we are on a bbPress forum post


	if ( 0 != $output ) {
		/*
		 * NOTE: this gets the image sized as on the page, size not guaranteed,
		 * may also get an external image so no guarantee thumbnail is available  */
		if ( $tag ) {
			$urls = $matches [ 0 ];
		} else {
			$urls = $matches [ 1 ];
		}
		foreach ( $urls as $url ) {
			if ( (strpos( $url, 'cat-generator-avatars' ) === false) && (strpos( $url, 'badge' ) === false) && (strpos( $url, 'avatar' ) === false) ) {
				$first_img = $url;
				//for forums, continue to last image, otherwise get first image
				if ( ! $forum_id ) {
					break;
				}
			} else {
				$last_avatar = $url;
			}
		}
	}

	if ( empty( $first_img ) && $fallback ) {
		if ( is_archive() ) {
			$first_img = ($forum_id ) ? get_forum_noimage() : get_noimage();
		} elseif ( $last_avatar ) {
			$first_img = $last_avatar;
		} elseif ( $forum_id ) { //last chance check for bbPress
			$thumbnail = wp_get_attachment_image_src( get_post_thumbnail_id( $forum_id ), 'medium' );
			if ( $thumbnail ) {
				$first_img = $thumbnail[ 0 ];
			}
		}
		if ( empty( $first_img ) ) {
			$first_img = get_noimage();
		}
	}
	if ( $returntag && $first_img ) {
		$first_img = '<img src="' . $first_img . '" />';
	}
	return $first_img;
}

/**
 * Filter: 'wpseo_pre_analysis_post_content' - Allow filtering the content before analysis
 * this is only used to get images to output meta so this filter forces it to see our preferred images
 *
 * @param string $content - post content
 * @param WP_Post $post - post
 *
 * @return string image
 */
function inkston_featured_img( $content, $post ) {
	return inkston_featured_img_tag( $content, true );
}

add_filter( 'wpseo_pre_analysis_post_content', 'inkston_featured_img', 10, 2 );

/*
 * worker function for image tiles
 */
function tile_thumb() {
	$thumbnail = wp_get_attachment_image_src( get_post_thumbnail_id(), 'thumbnail' );
	if ( $thumbnail ) {
		$thumbnail = $thumbnail[ 0 ];
	} else {
		$thumbnail = inkston_catch_image();
	}
	/* $extract = inkston_get_excerpt( 25 );  just plain text, use inkston_excerpt( 40 ); for html.. */
	$title			 = get_the_title();
	$class			 = "tile h-entry";
	$beforelink		 = '';
	$excerpt_length	 = 20;
	global $post;
	if ( $post->post_type == 'product' ) {
		$product	 = wc_get_product( $post );
		$beforelink	 .= wc_get_rating_html( $product->get_average_rating(), $product->get_rating_count() );
		if ( $product->is_on_sale() ) {
			$beforelink .= apply_filters( 'woocommerce_sale_flash', '<span class="onsale">' . esc_html__( 'Sale!', 'woocommerce' ) . '</span>', $post, $product );
		}
		$excerpt_length = 13; //shorter excerpt for products to allow space for price etc
	}

	if ( strrpos( $thumbnail, "no-image.png" ) !== false ) {
		$class .= ' nopic';
	}
	?><div class="<?php echo($class); ?>" id="post-<?php the_ID(); ?>" style="background-image:url( '<?php echo $thumbnail; ?>');"><?php echo($beforelink); ?><a href="<?php the_permalink(); ?>" rel="bookmark"><h3><?php echo($title); ?></h3><p class="p-summary"><?php echo(inkston_get_excerpt( $excerpt_length )); ?></p></a></div>
	<?php
}

/*
 * set featured image in a post to a url
 * based on remicorson
 *
 * @param string $post_id   The post whose featured image should be updated.
 * @param string $image_url The new url to use.
 *
 * @return int id of attachment post for saved image
 */
function ii_set_featured_image( $post_id, $image_url ) {
	$image_name			 = strrchr( $image_url, '/' );
	$upload_dir			 = wp_upload_dir(); // Set upload folder
	$image_data			 = file_get_contents( $image_url ); // Get image data
	$unique_file_name	 = wp_unique_filename( $upload_dir[ 'path' ], $image_name ); // Generate unique name
	$filename			 = basename( $unique_file_name ); // Create image file name
// Check folder permission and define file location
	if ( wp_mkdir_p( $upload_dir[ 'path' ] ) ) {
		$file = $upload_dir[ 'path' ] . '/' . $filename;
	} else {
		$file = $upload_dir[ 'basedir' ] . '/' . $filename;
	}

// Create the image  file on the server
	file_put_contents( $file, $image_data );

// Check image file type
	$wp_filetype = wp_check_filetype( $filename, null );

// Set attachment data
	$attachment = array(
		'post_mime_type' => $wp_filetype[ 'type' ],
		'post_title'	 => sanitize_file_name( $filename ),
		'post_content'	 => '',
		'post_status'	 => 'inherit'
	);

// Create the attachment
	$attach_id = wp_insert_attachment( $attachment, $file, $post_id );

// Include image.php
	require_once(ABSPATH . 'wp-admin/includes/image.php');

// Define attachment metadata
	$attach_data = wp_generate_attachment_metadata( $attach_id, $file );

// Assign metadata to attachment
	wp_update_attachment_metadata( $attach_id, $attach_data );

// And finally assign featured image to post
	set_post_thumbnail( $post_id, $attach_id );

	return $attach_id;
}

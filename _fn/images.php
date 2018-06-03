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
	$title		 = get_the_title( $attachment->post_parent );
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
function inkston_catch_image() {
    global $post, $posts;
    $first_img = '';
    if ( function_exists( 'bbp_is_single_user' ) && bbp_is_single_user() ) {
	$first_img = inkston_featured_img_tag( get_avatar( bbp_get_displayed_user_field( 'user_email', 'raw' ) ), false );
    } elseif ( is_single() || ($post && $post->ID) ) {
	$first_img = inkston_featured_img_tag( $post->post_content, false );
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
function inkston_featured_img_tag( $content, $returntag ) {
    $first_img	 = '';
    $last_avatar	 = '';

    //check if we are on a bbPress forum post
    global $post;
    $forum_id = 0;
    if ( $post ) {
	switch ( $post->post_type ) {
	    case 'topic':
		$forum_id	 = bbp_get_topic_forum_id( $post->ID );
		break;
	    case 'reply':
		$forum_id	 = bbp_get_reply_forum_id( $post->ID );
		break;
	    default:
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
		$url = ($returntag) ? $imgtag->ownerDocument->saveXML( $imgtag ) : $imgtag->getAttribute( 'src' );
		//$url = $imgtag->getAttribute( 'src');
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
	    if ( $first_img	 = '' && $last_avatar	 = '' ) {
		return inkston_featured_img_tag_regex( $content, $returntag );
	    }
	}
    }

    if ( empty( $first_img ) ) {
	if ( is_archive() ) {
	    $first_img = ($forum_id ) ? get_forum_noimage() : get_noimage();
	    if ( $returntag ) {
		$first_img = '<img src="' . $first_img . '" />';
	    }
	} elseif ( $last_avatar ) {
	    $first_img = $last_avatar;
	} elseif ( $forum_id ) { //last chance check for bbPress
	    $thumbnail = wp_get_attachment_image_src( get_post_thumbnail_id( $forum_id ), 'medium' );
	    if ( $thumbnail ) {
		$first_img = $thumbnail[ 0 ];
	    }
	    if ( empty( $first_img ) ) {
		$first_img = get_noimage();
	    }
	    if ( $returntag ) {
		$first_img = '<img src="' . $first_img . '" />';
	    }
	}
    }
    return $first_img;
}

/*
 * this regex didn't quite cope with the wide variety of inputs
 */
function inkston_featured_img_tag_regex( $content, $tag ) {
    $first_img	 = '';
    $last_avatar	 = '';
    $output		 = preg_match_all( '/<img.+src=[\'"]([^\'"]+)[\'"].*>/i', $content, $matches );
    /* $output = preg_match_all("/<img\s[^>]*?src\s*=\s*['\"]([^'\"]*?)['\"][^>]*?>", $content, $matches); */
    //$output = preg_match_all( '/<img.+src=[\'"]([^\'"]+)[\'"].*>/i', $content, $matches);
    //check if we are on a bbPress forum post
    global $post;
    $forum_id	 = 0;
    if ( $post ) {
	switch ( $post->post_type ) {
	    case 'topic':
		$forum_id	 = bbp_get_topic_forum_id( $post->ID );
		break;
	    case 'reply':
		$forum_id	 = bbp_get_reply_forum_id( $post->ID );
		break;
	    default:
	}
    }

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
    if ( empty( $first_img ) ) {
	if ( is_archive() ) {
	    $first_img = ($forum_id ) ? get_forum_noimage() : get_noimage();
	} elseif ( $last_avatar ) {
	    $first_img = $last_avatar;
	} elseif ( $forum_id ) { //last chance check for bbPress
	    $thumbnail = wp_get_attachment_image_src( get_post_thumbnail_id( $forum_id ), 'medium' );
	    if ( $thumbnail ) {
		$first_img = $thumbnail[ 0 ];
	    }
	    if ( empty( $first_img ) ) {
		$first_img = get_noimage();
	    }
	}
    }
    return $first_img;
}

/**
 * Filter: 'wpseo_pre_analysis_post_content' - Allow filtering the content before analysis
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

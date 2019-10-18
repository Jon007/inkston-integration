<?php
/*
 * bbpress specific customisations
 *
 * NOTE ON IMAGE UPLOAD FOR BBPRESS
 *  - since wp4.8, tinymce is no longer inserting sizes for images
 *    this breaks photoswipe (separate issue perhaps)
 *  - allowing Add Media on bbPress doesnt work by default because the
 *  attach image permissions are checked before any draft post exists
 *    - when adding new topic,  parent forum permissions are checked
 *    - when adding new reply, parent topic permissions are checked
 * the effect of this edit_others is required on forums and topics...
 * .. they shouldnt actually be able to edit .. and an edit url will give
 * Sorry, you are not allowed to edit posts in this post type.
 * it is just this particular capability which is needed
 *
 */

/*
 * when redirecting after a new topic, first check the topic has not been
 * set to pending
 */
function ii_bbp_new_topic_redirect_to( $redirect_url, $redirect_to, $topic_id ) {
	$topic_status	 = get_post_field( 'post_status', $topic_id );
	if ( $topic_status == bbp_get_pending_status_id() ) {
		/* bbp uses WP_Error information which is lost in redirect
		  bbp_add_error( 'bbp_topic_content', __( '<strong>MODERATION</strong>: thanks for your post, it has been saved correctly and will become visible when approved by a moderator.', 'inkston-integration' ) );
		 */
		$forum_id		 = bbp_topic_forum_id( $topic_id );
		$redirect_url	 = bbp_get_forum_permalink( $forum_id ) . '?iimod=' . $topic_id;
	}
	return $redirect_url;
}

add_filter( 'bbp_new_topic_redirect_to', 'ii_bbp_new_topic_redirect_to', 10, 3 );
add_filter( 'bbp_new_reply_redirect_to', 'ii_bbp_new_topic_redirect_to', 10, 3 );

/*
 * if redirected from a pending post, show an admin notice
 */
function ii_bb_admin_notice__info() {
	error_log( 'entered ii_bb_admin_notice__info' );
	if ( isset( $_GET[ 'iimod' ] ) ) {
		$post_id	 = $_GET[ 'iimod' ]; //optionally, check the post to customise the message further
		$post_status = get_post_field( 'post_status', $post_id );
		switch ( $post_status ) {
			case bbp_get_pending_status_id():
				?>
				<div class="notice notice-success is-dismissible">
					<div class="bbp-template-notice info">
						<p><?php _e( 'Thanks for your post!<br />Your writing has been saved correctly and will become visible as soon as it is checked by a moderator.', 'inkston-integration' ); ?></p>
					</div>
				</div>
				<?php
				return;
			case bbp_get_public_status_id():
				$post_link = get_permalink( $post_id );
				?>
				<div class="notice notice-success is-dismissible">
					<div class="bbp-template-notice info">
						<p><?php _e( 'This post is now published and visible', 'inkston-integration' ); ?>
							<a href="<?php echo $post_link; ?>"> <?php _e( 'here', 'inkston-integration' ); ?> </a>
							.</p>
					</div>
				</div>
				<?php
				return;
		}
	}
}

add_action( 'bbp_template_before_single_forum', 'ii_bb_admin_notice__info' );
//add_action( 'admin_notices', 'ii_bb_admin_notice__info' );

/*
 * attempt to ensure featured images are set to avoid repeated deductions later..
 */
function ii_bbp_set_featured_image( $post_id ) {
	$post				 = get_post( $post_id );
	$post_thumbnail_id	 = get_post_thumbnail_id( $post );
	if ( ! $post_thumbnail_id ) {
		$image_url = inkston_catch_image( $post_id );
		if ( $image_url && $image_url != get_noimage() ) {
			$post_thumbnail_id = ii_set_featured_image( $post_id, $image_url );
			if ( bbp_is_reply( $post_id ) ) {
				set_post_thumbnail( bbp_get_reply_topic_id( $post_id ), $post_thumbnail_id );
			}
		}
	}
}

add_action( 'bbp_new_topic', 'ii_bbp_set_featured_image', 10, 1 );
add_action( 'bbp_edit_topic', 'ii_bbp_set_featured_image', 10, 1 );
add_action( 'bbp_new_reply', 'ii_bbp_set_featured_image', 10, 1 );
add_action( 'bbp_edit_reply', 'ii_bbp_set_featured_image', 10, 1 );
//do_action( 'bbp_edit_topic', $topic_id, $forum_id, $anonymous_data, $topic_author , true /* Is edit */ );
/**
 * Filters the forum reply link in feeds to force unique link to stop caching.
 *    otherwise Facebook, Twitter etc cache previous image on same url
 *
 *
 * @param string $url     The complete URL including scheme and path.
 * @param int    $user_id The user ID.
 * @param string $path    Path relative to the URL. Blank string if no path is specified.
 * @param string $scheme  Scheme to give the URL context. Accepts 'http', 'https', 'login',
 *                        'login_post', 'admin', 'relative' or null.
 */
function ink_bbp_feed_reply_url( $url, $reply_id, $redirect_to ) {
    if ( is_feed() || ( stripos( $_SERVER[ 'REQUEST_URI' ], '/feed' )) ) {
	$topicslug = bbp_get_topic_slug();
	if ( stripos( $url, $topicslug ) ) {
	    $url = str_replace( '#post-' . $reply_id, $reply_id . '/#post-' . $reply_id, $url );
	} else {
	    //fix, bbPress not return correct links for topics in feed
	    $url = bbp_get_topic_last_reply_permalink();
	    //$url = str_replace( '#post-' . $reply_id, $reply_id . '/#post-' . $reply_id, $url)
	}
    }
    return $url;
}

add_filter( 'bbp_get_reply_url', 'ink_bbp_feed_reply_url', 10, 3 );
/**
 * Avoid changing SEO canonical urls for replies: allow each reply to be new canonical.
 *
 * @param string $url     The complete URL including scheme and path.
 */
function ink_bbp_canonical( $canonical ) {
    if ( function_exists( 'is_bbpress' ) && is_bbpress() ) {
	$canonical = "https://" . $_SERVER[ 'HTTP_HOST' ] . $_SERVER[ 'REQUEST_URI' ];
    }
    return $canonical;
}

add_filter( 'wpseo_canonical', 'ink_bbp_canonical', 10, 1 );
add_filter( 'wpseo_opengraph_url', 'ink_bbp_canonical', 10, 1 );


/*
 * bbPress avatar size is insanely small
 */
function ink_min_avatar_size( $args ) {
    if ( isset( $args[ 'size' ] ) ) {
	$size = $args[ 'size' ];
	if ( is_numeric( $size ) ) {
	    if ( $size < 88 ) {
		$args[ 'size' ] = 88;
	    }
	}
    }
    return $args;
}

add_filter( 'bbp_after_get_author_link_parse_args', 'ink_min_avatar_size', 10, 1 );
add_filter( 'bbp_after_get_topic_author_link_parse_args', 'ink_min_avatar_size', 10, 1 );



/*
 * filter to add description after forums titles on forum index
 */
function rw_singleforum_description() {
    echo '<div class="bbp-forum-content">';
    echo bbp_forum_content();
    echo '</div>';
}

add_action( 'bbp_template_before_single_forum', 'rw_singleforum_description' );


/*
 * Add forum title to forum email alerts
 */
function ink_bbp_mail_alert( $message, $reply_id, $topic_id ) {
    $topic_title	 = strip_tags( bbp_get_topic_title( $topic_id ) );
    $forum_title	 = bbp_get_topic_forum_title( $topic_id );

	$messageheader	 = sprintf( __( 'New post in Forum "%1$s", Topic "%2$s".', 'inkston-integration' ), $forum_title, $topic_title
    );
    $profilelink	 = network_site_url() . 'community/my-profile/';
	$messagefooter	 = sprintf( __( 'Or visit your profile page to review all your subscriptions: %1$s', 'inkston-integration' ), $profilelink
    );
	$messagefooter	 .= "\r\n" . "\r\n" . __( 'Thankyou for participating in Inkston Community', 'inkston-integration' );

    return $messageheader . "\r\n" . "\r\n" . $message . "\r\n" . $messagefooter;
}

add_filter( 'bbp_subscription_mail_message', 'ink_bbp_mail_alert', 10, 3 );

/*
 * filter forum email sending adress
 */
function ink_bbp_distributionaddress( $address ) {
	return __( 'forum-subscribers@inkston.com', 'inkston-integration' );
}

add_filter( 'bbp_get_do_not_reply_address', 'ink_bbp_distributionaddress', 10, 1 );

/*
 * allow shortcodes in forum posts (or not?)
 */
function pw_bbp_shortcodes( $content, $reply_id ) {
    if ( ( ! is_feed() ) && ( stripos( $_SERVER[ 'REQUEST_URI' ], '/feed' ) === FALSE ) ) {
	//this blanket allows shortcodes on the front end
	//$reply_author = bbp_get_reply_author_id( $reply_id );
	//if( user_can( $reply_author, pw_bbp_parse_capability() ) ){
	return do_shortcode( $content );
	//}
    }
    return strip_shortcodes( $content ) . ink_bbp_hashtags( $reply_id );
}

add_filter( 'bbp_get_reply_content', 'pw_bbp_shortcodes', 10, 2 );
add_filter( 'bbp_get_topic_content', 'pw_bbp_shortcodes', 10, 2 );

/*
 * unused idea to make forum features dependent on user level
  function pw_bbp_parse_capability()
  {
  return apply_filters( 'pw_bbp_parse_shortcodes_cap', 'publish_forums');
  }
 */

/*
 * get hashtags for current bbp item tags
 * @param int    $topic_or_reply_id    id of current .
 */
function ink_bbp_hashtags( $topic_or_reply_id ) {
    if ( ! $post = get_post( $topic_or_reply_id ) )
	return false;

    if ( $post->post_type != 'topic' ) {
	$topic_or_reply_id = bbp_get_reply_topic_id();
    }

    $terms		 = get_the_terms( $topic_or_reply_id, bbp_get_topic_tag_tax_id() );
    $hashtags	 = '';
    if ( is_array( $terms ) ) {
	$hashtags	 = wp_list_pluck( $terms, 'name' );
	$hashtags	 = ink_hashtag_implode( ' #', $hashtags );
    }
    return $hashtags;
}

/**
 * Remove styles on non-bbPress page
 *
 * @param   array   $styles      styles bbPress wants to add
 * @return  array   styles to queue
 */
function remove_bbpress_styles( $styles ) {
    if ( ( ! function_exists( 'is_bbpress' )) || ( ( ! is_bbpress()) && ( ! is_front_page())) ) {
	return [];
    } else {
	/* remove minification as bbPress 2.6 now has this logic
	  if ( (! defined( 'SCRIPT_DEBUG') ) || (SCRIPT_DEBUG==false) ){
	  foreach ($styles as $key => $style){
	  $style['file'] = str_replace( '.css', '.min.css', $style['file']);
	  $styles[$key]=$style;
	  }
	  }
	 */
	return $styles;
    }
}

add_filter( 'bbp_default_styles', 'remove_bbpress_styles', 10, 1 );
/**
 * Remove scripts on non-bbPress page
 *
 * @param   array   $scripts      scripts bbPress wants to add
 * @return  array   scripts to queue
 */
function remove_bbpress_scripts( $scripts ) {
    if ( ! is_bbpress() ) {
	remove_action( 'wp_print_scripts', 'bbpress_auto_subscription_ajax_load_scripts' );
	return [];
    } else {
	/* bbPress doesn't actually supply minified scripts
	  foreach ($scripts as $key => $script){
	  $script['file'] = str_replace( '.js', '.min.js', $script['file']);
	  $scripts[$key]=$style;
	  }
	 */
	return $scripts;
    }
}

add_filter( 'bbp_default_scripts', 'remove_bbpress_scripts', 10, 1 );



/* Forums: visual editor only, allow full screen  */
function bbp_enable_visual_editor( $args = array() ) {
    $args[ 'tinymce' ]	 = true;
    $args[ 'teeny' ]	 = false;
    $args[ 'quicktags' ]	 = false;
    $args[ 'fullscreen' ]	 = true;
    return $args;
}

add_filter( 'bbp_after_get_the_content_parse_args', 'bbp_enable_visual_editor' );

/* only allows simple formatting in pastes */
function bbp_tinymce_paste_plain_text( $plugins = array() ) {
    $plugins[] = 'paste';
    return $plugins;
}

add_filter( 'bbp_get_tiny_mce_plugins', 'bbp_tinymce_paste_plain_text' );

/* enable fullscreen on forum tinymce editor if wanted */
/*
  function re_enable_mce_full_screen( $plugins = array() ){
  $plugins[] = 'fullscreen';
  return $plugins;
  }
  add_filter( 'bbp_get_tiny_mce_plugins', 're_enable_mce_full_screen' );
 */


/*
 * this adds the login and register links underneath on a single topic so someone can leave a reply.
 * It uses the same logic as form-reply
 */
function ink_new_reply_login() {
    if ( ! bbp_current_user_can_access_create_reply_form() && ! bbp_is_topic_closed() && ! bbp_is_forum_closed( bbp_get_topic_forum_id() ) ) {
	?>
	<div style="line-height:3em">
			<a class="bbp-login-prompt" href="<?php echo wp_login_url( get_permalink() ); ?>" title="Login"><?php _e( 'Login or Register with email, Facebook, Google or LinkedIn account', 'inkston-integration' ) ?></a>
	</div>
	<?php
    }
}

add_action( 'bbp_template_after_single_topic', 'ink_new_reply_login' );
/*
 * this adds the login and register links underneath on a single forum so someone can start a topic.
 * It uses the same logic as form-topic
 */
function ink_new_topic_login() {
    if ( ! bbp_current_user_can_access_create_topic_form() && ! bbp_is_forum_closed() ) {
	?>
	<div style="line-height:3em">
			<a class="bbp-login-prompt" href="<?php echo wp_login_url( get_permalink() ); ?>" title="Login"><?php _e( 'Login or Register with email, Facebook, Google or LinkedIn account', 'inkston-integration' ) ?></a>
	</div>
	<?php
    }
}

add_action( 'bbp_template_after_single_forum', 'ink_new_topic_login' );

/*
 * enqueue css if this is enabled
 */
inkston_integration::get_instance()->ii_enqueue_script( 'ii-bbpress' );

/*
 * attempt to handle generic request for current user
 *
 * if the url is passed as forums/users/current/
 * this function detects that the permalink has been set to bbp_user=current
 * and
 *  - if the user is logged on, the query is changed to the current user
 *  - if the user is not logged on, the user is redirected to login screen
 *
 */
function ink_bbp_request_current_user( $query_vars ) {
	if ( isset( $query_vars[ 'bbp_user' ] ) ) {
		switch ( $query_vars[ 'bbp_user' ] ) {
			case 'current':
				if ( get_current_user_id() ) {
					$currentuser				 = wp_get_current_user();
					$query_vars[ 'bbp_user' ]	 = $currentuser->user_nicename;  //nicename is the wp_users field which is used as slug
					if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
						error_log( 'bbpress current profile set current user to ' . $currentuser->user_login . ' nicename ' . $currentuser->user_nicename );
					}
				} else {
					if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
						error_log( 'bbpress current profile page called with no user logged in, calling auth_redirect ' );
					}
					auth_redirect();
				}
		}
	}
	return $query_vars;
}

add_filter( 'bbp_request', 'ink_bbp_request_current_user', 10, 1 );

/*
 * enable media upload on topics and replies
 * - SEE HEADER NOTES FOR IMPORTANT CONFIGURATION REQUIREMENTS
 */
function ii_bbpress_upload_media( $args ) {
	$args[ 'media_buttons' ] = true;

	return $args;
}

add_filter( 'bbp_after_get_the_content_parse_args', 'ii_bbpress_upload_media' );

/*
 * filter SEO descriptions to catch current reply and get description of that
 */
function ink_seo_description( $description ) {
	//check if we are on a bbPress forum post
	global $post;
	if ( $post ) {
		switch ( $post->post_type ) {
			case 'topic':
			case 'reply':
				//if the request is for a particular topic reply, add excerpt of the reply to the current description
				global $wp;
				if ( isset( $wp->query_vars[ 'page' ] ) ) {
          $pageid = $wp->query_vars[ 'page' ];
          //$pageid = bbp_get_paged_rewrite_id();
          if ( $pageid ) {
            $pageid = intval( $pageid );
            if ( $pageid != $post->ID ) {
              //$replypost	 = bbp_get_reply( $pageid );
              $replydesc = inkston_get_excerpt( inkston_excerpt_length( 25 ), false, $pageid );
              if ( $replydesc ) {
                $description = $replydesc;
              }
            }
          }
				}
				break;
			default:
		}
	}
	return $description;
}

add_filter( 'wpseo_opengraph_desc', 'ink_seo_description', 10, 1 );
add_filter( 'wpseo_twitter_description', 'ink_seo_description', 10, 1 );
add_filter( 'wpseo_metadesc', 'ink_seo_description', 10, 1 );

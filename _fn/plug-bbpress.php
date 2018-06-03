<?php
/*
 * bbpress specific customisations
 */
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

    $messageheader	 = sprintf( __( 'New post in Forum "%1$s", Topic "%2$s".', 'photoline-inkston' ), $forum_title, $topic_title
    );
    $profilelink	 = network_site_url() . 'community/my-profile/';
    $messagefooter	 = sprintf( __( 'Or visit your profile page to review all your subscriptions: %1$s', 'photoline-inkston' ), $profilelink
    );
    $messagefooter	 .= "\r\n" . "\r\n" . __( 'Thankyou for participating in Inkston Community', 'photoline-inkston' );

    return $messageheader . "\r\n" . "\r\n" . $message . "\r\n" . $messagefooter;
}

add_filter( 'bbp_subscription_mail_message', 'ink_bbp_mail_alert', 10, 3 );

/*
 * filter forum email sending adress
 */
function ink_bbp_distributionaddress( $address ) {
    return __( 'forum-subscribers@inkston.com', 'photoline-inkston' );
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
	    <a class="bbp-login-prompt" href="<?php echo wp_login_url( get_permalink() ); ?>" title="Login"><?php _e( 'Login or Register with email, Facebook, Google or LinkedIn account', 'photoline-inkston' ) ?></a>
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
	    <a class="bbp-login-prompt" href="<?php echo wp_login_url( get_permalink() ); ?>" title="Login"><?php _e( 'Login or Register with email, Facebook, Google or LinkedIn account', 'photoline-inkston' ) ?></a>
	</div>
	<?php
    }
}

add_action( 'bbp_template_after_single_forum', 'ink_new_topic_login' );

<?php
/*
 * customisations for badgeos
 * Shortcodes
 *  [inkpoints] number of points
 *  [inklevel style="brushes|int|img|url|imgurl|imglink|text|textlink|html|score"]
 *
 * TODO: certain functions limited for site id 2: parameterize or make safe and remove
 */
/**
 * Filters the avatar to retrieve, assigning BadgeOS avatar if BadgeOS activated
 * and not already assigned a cat generator avatar
 *
 * @since 2.5.0
 * @since 4.2.0 The `$args` parameter was added.
 *
 * @param string $avatar      &lt;img&gt; tag for the user's avatar.
 * @param mixed  $id_or_email The Gravatar to retrieve. Accepts a user_id, gravatar md5 hash,
 *                            user email, WP_User object, WP_Post object, or WP_Comment object.
 * @param int    $size        Square avatar width and height in pixels to retrieve.
 * @param string $default     URL for the default image or a default type. Accepts '404', 'retro', 'monsterid',
 *                            'wavatar', 'indenticon','mystery' (or 'mm', or 'mysteryman'), 'blank', or 'gravatar_default'.
 *                            Default is the value of the 'avatar_default' option, with a fallback of 'mystery'.
 * @param string $alt         Alternative text to use in the avatar image tag. Default empty.
 * @param array  $args        Arguments passed to get_avatar_data(), after processing.
 */
function ink_filter_avatar( $avatar, $id_or_email, $size, $default, $alt, $args ) {
	//first, if user already has an avatar, which is not cat-generator avatar, return it
	if ( strpos( $avatar, 'cat-generator-avatars' ) === false ) {
		return $avatar;  //TODO: it could be nice to filter the title and add the user level
	}
	//similarly return if there is no user info to look up
	if ( ! $id_or_email ) {
		return $avatar;
	}

	//for now only badge avatar on community site
	//TODO: confirm we can remove this now
	//if (get_current_blog_id() != 2) {
	//    return $avatar;
	//}

	$title	 = '';
	$user_Id = 0;
	$user	 = false;
	if ( is_numeric( $id_or_email ) ) {
		$user_Id = intval( $id_or_email );
		if ( $user_Id ) {
			$user	 = get_user_by( 'ID', $user_Id );
			if ( $user ) {
        $title	 = $user->display_name;
      }
		}
	} elseif ( is_string( $id_or_email ) ) {
		$user = get_user_by( 'email', $id_or_email );
	} elseif ( $id_or_email instanceof WP_User ) {
		$user = $id_or_email;
	} elseif ( $id_or_email instanceof WP_Comment ) {
		if ( 0 < $id_or_email->user_id ) {
			$user = get_user_by( 'ID', $id_or_email->user_id );
		} else {
			$title = $id_or_email->comment_author_email;
		}
	} elseif ( $id_or_email instanceof WP_Post ) {
		$user = get_user_by( 'ID', $id_or_email->post_author );
	}
	if ( $user ) {
		$user_Id = $user->ID;
		$title	 = $user->display_name;
	}

	if ( is_numeric( $user_Id ) ) {

		$badge = get_user_level( array(
			'user_id'	 => $user_Id,
			'style'		 => 'imgurl' ) );

		if ( $badge != __( 'No badges yet', 'inkston-integration' ) ) {
			$levelname = get_user_level( array(
				'user_id'	 => $user_Id,
				'style'		 => 'text' ) );
			return '<span class="avatar-container">' .
			'<img alt="' . esc_attr( $levelname ) . '" src="' . $badge .
			'" title="' . $title . "\n(" . esc_attr( $levelname ) . ')' .
			'" class="avatar avatar-' . $size .
			' " height="' . $size . '" width="' . $size .
			'" style="height:' . $size . 'px;width:' . $size . 'px" />' .
			//'<div class="avatar"' .
			get_user_level( array(
				'user_id'	 => $user_Id,
				'style'		 => 'brushes' ) )
			. '</span>';
		}
	}
	return $avatar;
}

add_filter( 'get_avatar', 'ink_filter_avatar', 200, 6 );



/*
 * Shortcode [inkpoints]
 */
function get_user_points( $atts = array() ) {
	$a = shortcode_atts( array(
		'user_id' => ink_user_id(),
	), $atts );
	return badgeos_get_users_points( $a[ 'user_id' ] );
}

add_shortcode( 'inkpoints', 'get_user_points' );

/*
 * get user level number, link, image, paintbrushes
 * Shortcode:
 *  [inklevel style="brushes|int|img|url|imgurl|imglink|text|textlink|html|score"]
 */
function get_user_level( $atts = array() ) {
	$a		 = shortcode_atts( array(
		'user_id'			 => ink_user_id(),
		'achievement_type'	 => 'badge', // A specific achievement type
		'size'				 => 'badgeos-achievement', //thumbnail image size
		'style'				 => 'html', // formatted output with image and text
	//'style' => 'full', // complete output including congratulation text
	//'style' => 'img', // image tag for current badge only
	//'style' => 'imglink', // image tag for current badge wrapped in link
	//'style' => 'imgurl', // image url for current badge only
	//'style' => 'text', // text name for current badge only
	//'style' => 'url', // url for current badge only
	//'style' => 'textlink', // text name and link for current badge only
	//'style' => 'int', // level number (menu order +1) for current badge only
	//'style' => 'score', // highest badge and score details
	), $atts );
	$output	 = '';
	if ( function_exists( 'badgeos_get_user_achievements' ) ) {
		$user_achievements = badgeos_get_user_achievements( $a );
		if ( ! $user_achievements || sizeof( $user_achievements ) == 0 ) {
			return __( 'No badges yet', 'inkston-integration' );
		}

		$user_achievement_ids = wp_list_pluck( $user_achievements, 'ID' );

		$achievements = badgeos_get_achievements( array(
			'post_type'			 => 'badge',
			'suppress_filters'	 => true,
			'numberposts'		 => 1,
			'orderby'			 => 'menu_order',
			'order'				 => 'DESC',
			'include'			 => $user_achievement_ids,
		) );

		$post = $achievements[ 0 ];
		switch ( $a[ 'style' ] ) {
			case 'brushes':
				$level	 = intval( $post->menu_order );
				$output	 = '<span title="' . $post->post_title .
				' ( ' . __( 'level', 'inkston-integration' ) . $level . ')' .
				'" class="brushlevel">';
				for ( $x = 1; $x <= $level; $x ++ ) {
					$output .= '<i class="fa fa-paint-brush"></i>';
				}
				$output			 .= '</span>';
				break;
			case 'int':
				$output			 = intval( $post->menu_order );
				break;
			case 'img':
				$output			 = get_the_post_thumbnail( $post, $a[ 'size' ] );
				break;
			case 'imgurl':
				$thumb_id		 = get_post_thumbnail_id( $post );
				$thumb_url_array = wp_get_attachment_image_src( $thumb_id, $a[ 'size' ], true );
				$output			 = $thumb_url_array[ 0 ];
				break;
			case 'text':
				$output			 = $post->post_title;
				break;
			case 'url':
				$output			 = get_permalink( $post );
				break;
			case 'imglink':
				$output			 = '<div class="badgeos-item-image"><a href="' . get_permalink( $post ) . '">' . get_the_post_thumbnail( $post, $a[ 'size' ] ) . '</a></div>';
				break;
			case 'textlink':
				$output			 = '<div class="badgeos-item-description"><h2 class="badgeos-item-title"><a href="' .
				get_permalink( $post ) . '">' . $post->post_title . '</a></h2></div>';
				break;
			case 'score':
				$output			 = '<div class="inkpoints"><div class="badgeos-item-image"><a href="' . get_permalink( $post ) . '">' . get_the_post_thumbnail( $post, $a[ 'size' ] ) . '</a></div>';
				$output			 .= '<div class="badgeos-item-description"><p>' .
				__( 'Current level: ', 'inkston-integration' ) .
				' <a href="' . get_permalink( $post ) . '">' . $post->post_title .
				' ( ' . __( 'level ', 'inkston-integration' ) . (intval( $post->menu_order )) . ')</a>' .
				'<br />' .
				sprintf( __( 'Current score: %1$s points.', 'inkston-integration' ), get_user_points() ) .
				'</p></div></div>';
				break;
			case 'html':  //one block with badge and badge description (not award message)
				$user_id		 = $a[ 'user_id' ];
				$output			 = badgeos_render_achievement( $post->ID, $user_id );
				break;
			case 'full':
			default:  //html
				/*            $output = '<div class="badgeos-item-image"><a href="' . get_permalink($post) . '">' . get_the_post_thumbnail($post) . '</a></div>';
				  $output .= '<div class="badgeos-item-description"><h2 class="badgeos-item-title"><a href="' .
				  get_permalink($post) . '">' . $post->post_title . '</a></h2>' .
				  '<div class="badgeos-item-excerpt">' . $post->excerpt . '</div>' .
				  '</div>';
				 */
				$user_id		 = $a[ 'user_id' ];
				$output			 = badgeos_render_achievement( $post->ID, $user_id );
				$output			 .= ' <br/>' . badgeos_render_earned_achievement_text( $post->ID, $user_id );
		}
	}
	return $output;
}

add_shortcode( 'inklevel', 'get_user_level' );
/**
 * Displays a members achievements
 *
 * @since 1.0.0
 */
function ink_bp_member_achievements_content() {

	$userid = ink_user_id();
	if ( ! $userid ) {
		return;
	}
	if ( ! function_exists( 'badgeos_get_network_achievement_types_for_user' ) ) {
		return;
	}
	$achievement_types	 = badgeos_get_network_achievement_types_for_user( $userid );
	// Eliminate step cpt from array
	if ( ( $key				 = array_search( 'step', $achievement_types ) ) !== false ) {
		unset( $achievement_types[ $key ] );
		$achievement_types = array_values( $achievement_types );
	}

	$type = '';

	if ( is_array( $achievement_types ) && ! empty( $achievement_types ) ) {
		foreach ( $achievement_types as $achievement_type ) {
			$name	 = get_post_type_object( $achievement_type )->labels->name;
			$slug	 = str_replace( ' ', '-', strtolower( $name ) );
			if ( $slug && strpos( $_SERVER[ 'REQUEST_URI' ], $slug ) ) {
				$type = $achievement_type;
			}
		}
		if ( empty( $type ) )
			$type = $achievement_types[ 0 ];
	}

	$atts = array(
//		'type'        => $type,
		'type'			 => 'badge,point',
		'limit'			 => '10',
		'show_filter'	 => 'false',
		'show_search'	 => 'false',
		'group_id'		 => '0',
		'user_id'		 => $userid,
		'orderby'		 => 'menu_order',
		'order'			 => 'ASC',
		'wpms'			 => badgeos_ms_show_all_achievements(),
	);
	echo badgeos_achievements_list_shortcode( $atts );
}

//function for highest badge: badgeos_achievements_list_shortcode limit to 1, return by last sort order..


/*
 * set achievements pending notification (so they are available to show to user after redirect)
 * @param int    $user_id    User ID.
 * @param int    $achievement_id new achievement awarded to this user
 * @return int|bool Meta ID if the key didn't exist, true on successful update, false on failure.
 */
function ink_set_achievements_to_notify( $user_id, $achievement_id ) {
	if ( $achievement_id ) {
		//return update_user_meta($user_id, '_ink_badge_pending', $achievement_id);

		$achievements = ink_get_achievements_to_notify( $user_id );
		if ( $achievements ) {
			if ( ! is_array( $achievements ) ) {
				$achievements = explode( ',', '' . $achievements );
			}
			if ( ! in_array( $achievement_id, $achievements ) ) {
				array_push( $achievements, $achievement_id );
			}
		} else {
			$achievements[] = $achievement_id;
		}
		return update_user_meta( $user_id, '_ink_badge_pending', $achievements );
	} else {
		return false;
	}
}

if ( class_exists( 'bbPress' ) ) {
	/*
	 * logged achievement not notified to user yet in user meta
	 */
	function ink_get_achievements_to_notify( $user_id ) {
		return get_user_meta( $user_id, '_ink_badge_pending', true );
	}

	/*
	 * clear logged achievement from user meta (after notification)
	 */
	function ink_clear_achievements_to_notify( $user_id ) {
		update_user_meta( $user_id, '_ink_badge_pending', '' );
	}

	//can't actually print the messages as we are redirected on successful post ....
	function ink_print_achievement_messages() {
		$user_id = get_current_user_id();
		if ( $user_id ) {
			$achievements = ink_get_achievements_to_notify( $user_id );
			if ( is_array( $achievements ) && count( $achievements ) > 0 ) {
				?><div class="bbp-template-notice"><p><?php _e( 'Congratulations you have been awarded:', 'inkston-integration' ) ?></p><?php
						for ( $i = 0, $size = count( $achievements ); $i < $size; ++ $i ) {
							if ( is_numeric( $achievements[ $i ] ) ) {
								$achievement_type = get_post_type( $achievements[ $i ] );
								switch ( $achievement_type ) {
									case 'badge':
									case 'point':
										echo(badgeos_render_achievement( $achievements[ $i ], $user_id ));
										echo(badgeos_render_earned_achievement_text( $achievements[ $i ], $user_id ));
										break;
									default:
								}
							}
						}
						//TODO: how do we know to clear, maybe there is a redirect and this is never shown??
						//maybe need acknowledgement button
						ink_clear_achievements_to_notify( $user_id )
						?></div><?php
			}
		}
	}

	add_action( 'bbp_template_notices', 'ink_print_achievement_messages' );
	function ink_add_achievement_messages( $user_id, $achievement_id, $this_trigger, $site_id, $args ) {
		//for now only process these alerts on community site since the post ids from main site are not synced to child
		if ( $site_id != 2 ) {
			//potential to add woocommerce alerts for site 1...
			//or key by site id for later retrieval
			return false;
		}
		$achievement_type = get_post_type( $achievement_id );
		switch ( $achievement_type ) {
			case 'badge':
			case 'point':
				ink_set_achievements_to_notify( $user_id, $achievement_id );
				break;
			default:
				return false;
		}

		//users profile is set to allow badgeos notification emails
		if ( badgeos_can_notify_user( $user_id ) ) {
			if ( 'badge' == $achievement_type ) {
				$to_email = get_userdata( $user_id )->user_email;

				$blog_name	 = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
				$subject	 = $blog_name . ' ' . __( 'your contribution has unlocked an award', 'inkston-integration' );

				$profilelink	 = network_site_url() . 'community/my-profile/';
				$messagefooter	 = sprintf( __( 'Visit <a href="%1$s">your profile page</a> to see details of your awards and turn these notifications on or off.', 'inkston-integration' ), $profilelink
				);

				$message = __( 'You were awarded this badge for making contributions to ', 'inkston-integration' );
				$message .= ' ' . __( 'Inkston Oriental Art Comunity', 'inkston-integration' );
				$message .= ' <br/>' . badgeos_render_achievement( $achievement_id, $user_id );
				$message .= ' <br/>' . badgeos_render_earned_achievement_text( $achievement_id, $user_id );
				$message .= ' <br/><br/>' . $messagefooter;
				$message .= ' <br/><br/>' . __( 'Thankyou for participating in Inkston Community', 'inkston-integration' );

				// Setup "From" email address
				$from_email	 = __( 'rewards@inkston.com', 'inkston-integration' );
				// Setup the From header
				$headers	 = array( 'From: ' . get_bloginfo( 'name' ) . ' <' . $from_email . '>',
					'Content-Type: text/html; charset=UTF-8' );

				// Send notification email
				wp_mail( $to_email, $subject, $message, $headers );
			}
		}
	}

	add_action( 'badgeos_award_achievement', 'ink_add_achievement_messages', 10, 5 );
}

/*
 * in bbpress and badgeos context, return displayed user id rather than current user id
 * This is for showing points/badge of author when viewing author profile
 * (otherwise would show current logged in user points rather than author points)
 */
function ink_user_id() {
	if ( function_exists( 'bbp_get_displayed_user_id' ) ) {
		return bbp_get_displayed_user_id();
	} else {
		return get_current_user_id();
	}
}

/* idea to issue points for creating directory listings, didn't quite seem to work..
  function ink_badge_triggers($triggers){
  $triggers['badgeos_new_wpbdp_listing'] = __( 'Publish a new directory listing', 'inkston-integration' );
  return $triggers;
  }
  add_filter( 'badgeos_activity_triggers', 'ink_badge_triggers', 10, 1);
 */


/* minor badgeos css fix */
function ii_badge_customizer_style() {
	return('.badgeos-no-results{display:none;}');
}

wp_add_inline_style( 'ii-body', ii_badge_customizer_style() );

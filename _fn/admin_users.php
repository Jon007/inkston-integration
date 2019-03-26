<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
function ii_customer_get_total_spent_query( $query, $customer ) {
	if ( 1 == 2 ) {
		echo $query;
	}
	return $query;
}

//add_filter( 'woocommerce_customer_get_total_spent_query', 'ii_customer_get_total_spent_query', 10, 2 );

/*
 * call the function to prime the user info cache and recalculate scores for accurate filtering and sorting
 */
function ii_recalculate_users() {
	$users = get_users( array( 'fields' => array( 'ID' ) ) );
	foreach ( $users as $userIdObj ) {
		$user = get_user_by( 'ID', $userIdObj->ID );
		if ( $user ) {
			ii_user_info( $user );
		} else {
			error_log( 'user may not be valid:' . $userId );
		}
	}
	return count( $users );
}

/*
 * recalculate users if previous calculation too old
 */
function ii_maybe_recalculate_users() {
	$tKey		 = 'iiusercache';
	$isCached	 = get_transient( $tKey );
	if ( ! $isCached ) {
		$userCount = ii_recalculate_users();
		set_transient( $tKey, $userCount, 36000 ); //cached for 10 hours
	}
}

function ii_users_list_table_query_args( $args ) {
	//executes before user query so recalc could be done here
	ii_maybe_recalculate_users();
	return $args;
}

add_filter( 'users_list_table_query_args', 'ii_users_list_table_query_args' );
/**
 * Add additional columns to users table
 *
 * @param array $columns
 *
 * @return mixed
 */
function ii_add_users_columns( $columns ) {
	if ( ! isset( $columns[ 'registered' ] ) ) {
		$columns[ 'registered' ] = __( 'Registered', 'inkston-integration' );
	}
	$columns[ 'wc_last_active' ] = __( 'WC Last Active', 'inkston-integration' );
	if ( ! isset( $columns[ 'money_spent' ] ) ) {
		$columns[ 'money_spent' ] = __( 'Money spent', 'inkston-integration' );
	}
	$columns[ 'mailpoet' ]				 = __( 'Mailpoet status', 'inkston-integration' );
	$columns[ 'thechamp_provider' ]		 = __( 'Social Login', 'inkston-integration' );
	$columns[ 'commment_count' ]		 = __( 'Comments and Reviews', 'inkston-integration' );
	$columns[ 'wpbdp_listing' ]			 = __( 'Artist Listing', 'inkston-integration' );
	$columns[ 'wp_2__bbp_topic_count' ]	 = __( 'Forum Topics', 'inkston-integration' );
	$columns[ 'wp_2__bbp_reply_count' ]	 = __( 'Forum Replies', 'inkston-integration' );
	$columns[ 'user_score' ]			 = __( 'User score', 'inkston-integration' );
	return $columns;
}

add_filter( 'manage_users_columns', 'ii_add_users_columns', 5, 1 );
add_filter( 'wpmu_users_columns', 'ii_add_users_columns', 15, 1 );
/*
 * only registers the columns as sortable, doesnt actually make the sort work..
 */
function ii_users_sortable_columns( $columns ) {
	//$columns = ii_add_users_columns( $columns );
	//To make a column 'un-sortable' remove it from the array unset($columns['date']);
	if ( ! isset( $columns[ 'registered' ] ) ) {
		$columns[ 'registered' ] = 'id';
	}

	$columns[ 'wc_last_active' ]		 = 'wc_last_active';
	$columns[ 'money_spent' ]			 = '_money_spent';
	$columns[ 'wp_2__bbp_topic_count' ]	 = 'wp_2__bbp_topic_count';
	$columns[ 'wp_2__bbp_reply_count' ]	 = 'wp_2__bbp_reply_count';
	$columns[ 'user_score' ]			 = 'user_score';
	$columns[ 'thechamp_provider' ]		 = 'thechamp_provider';
	//$columns[ 'wpbdp_listing' ]			 = 'wpbdp_listing';

	return $columns;
}

add_filter( 'manage_users_sortable_columns', 'ii_users_sortable_columns' );
//not currently supported for network users
add_filter( 'wpmu_users_sortable_columns', 'ii_users_sortable_columns' );
/*
 * added sorting by user metadata
 */
function ii_users_sort( $query ) {
	$orderby = $query->get( 'orderby' );
	switch ( $orderby ) {
		case 'wc_last_active' :
		case 'wp_2__bbp_topic_count' :
		case 'wp_2__bbp_reply_count' :
		case '_money_spent':
		//case 'wpbdp_listing':
		case 'user_score':
			$query->set( 'orderby', 'meta_value_num' );
			$query->set( 'meta_key', $orderby );
			break;
		case 'thechamp_provider':
			$query->set( 'orderby', 'meta_value' );
			$query->set( 'meta_key', $orderby );
			break;
	}
}

add_action( 'pre_get_users', 'ii_users_sort' );
/*
 * get mailpoet subscription status from mailpoet table
 */
function ii_mailpoet_status( $userId ) {
	$query	 = "SELECT status
			FROM wp_mailpoet_subscribers
			WHERE wp_user_id = " . $userId
	;
	global $wpdb;
	$result	 = $wpdb->get_var(
	// phpcs:disable WordPress.WP.PreparedSQL.NotPrepared
	$query
	// phpcs:enable
	);
	if ( $result ) {
		return $result;
	} else {
		return 'Not found';
	}
}

/*
 *
 */
function ii_community_posts( $userid, $post_type = 'wpbdp_listing' ) {
	global $wpdb;
	$count = $wpdb->get_var( 'SELECT COUNT(*) FROM wp_2_posts where post_type = "wpbdp_listing" and post_status not in ("trash") and post_author=' . $userid );
	return $count;
}

/*
 * get abandoned cart info
 */
function ii_abandoned_carts( $userId ) {
	$query	 = "Select id, abandoned_cart_time, abandoned_cart_info, 0 as total "
	. "FROM `wp_ac_abandoned_cart_history_lite` WHERE recovered_cart=0 and user_type = 'registered' and user_id = " . $userId
	;
	global $wpdb;
	$result	 = $wpdb->get_results(
	// phpcs:disable WordPress.WP.PreparedSQL.NotPrepared
	$query
	// phpcs:enable
	);
	if ( $result ) {
		foreach ( $result as $cart_details ) {
			try {
				$cart_data = json_decode( stripslashes( $cart_details->abandoned_cart_info ) );
				if ( $cart_data ) {
					$cart_info	 = $cart_data->cart;
					$total		 = 0;

					if ( count( $cart_info ) > 0 ) {
						foreach ( $cart_info as $k => $v ) {
							$total += $v->line_total;
						}
						$cart_details->total = (function_exists( 'wc_price' )) ? wc_price( $total ) : $total;
					}
				}
			} catch ( Exception $e ) {
				error_log( $e->getMessage() );
			}
		}
		return $result;
	} else {
		return '';
	}
}

function ii_comment_count( $userId ) {
	global $wpdb;
	return $wpdb->get_var( 'SELECT COUNT(comment_ID) FROM wp_comments WHERE user_id = ' . $userId );
}

/**
 * Set data to additional columns to users table
 *
 * @param $val
 * @param $columnName
 * @param $userId
 *
 * @return false|string
 */
function ii__users_custom_column( $val, $columnName, $userId ) {
	$userinfofields = ii_user_info( get_user_by( 'ID', $userId ) );
	if ( $columnName == 'money_spent' ) {
		$columnName = '_money_spent';
	}
	if ( isset( $userinfofields[ $columnName ] ) ) {
		$value			 = $userinfofields[ $columnName ];
		$displayvalue	 = ' - ';
		if ( isset( $value[ 'display' ] ) && $value[ 'display' ] ) {
			$displayvalue = $value[ 'display' ];
		} elseif ( isset( $value[ 'data' ] ) && $value[ 'data' ] ) {
			$displayvalue = $value[ 'data' ];
			if ( is_array( $displayvalue ) ) {
				$displayvalue = recursive_filter_implode( ', ', $displayvalue );
			}
		}
		if ( isset( $value[ 'link' ] ) ) {
			return sprintf( '<a href="%s">%s</a>', $value[ 'link' ], $displayvalue );
		} else {
			return $displayvalue;
		}
	}
	return $val;
}

add_filter( 'manage_users_custom_column', 'ii__users_custom_column', 10, 3 );
add_action( 'wpmu_users_custom_column', 'ii__users_custom_column', 15, 3 );
/**
 * Render additional information for user
 *
 * @param WP_User $user
 */
function ii_user_profile_info( WP_User $user ) {
	$userId			 = $user->ID;
	$date_format	 = get_option( 'date_format' ) . ' H:i:s';
	$userinfofields	 = ii_user_info( $user );

	//carts section moved out of ii_user_info to save processing time
	$userinfofields[ '_woocommerce_persistent_cart' ] = array( 'caption' => __( 'Last Cart', 'inkston-integration' ), 'data' => $user->get( '_woocommerce_persistent_cart' ) );
	try {
		$carts								 = ii_abandoned_carts( $userId );
		$userinfofields[ 'abandoned_carts' ] = array( 'caption'	 => __( 'Abandoned Carts', 'inkston-integration' )
			, 'data'		 => $carts );

		//abandoned cart formats
		if ( $carts ) {
			$cart_display = '<div class="admin compact">';
			foreach ( $carts as $cart ) {
				$cart_display .= '<span style="min-width:200px;display:inline-block"><a href="' . network_site_url( '/wp-admin/admin.php?page=woocommerce_ac_page&action=orderdetails&id=' . $cart->id ) . '">' .
				date_i18n( $date_format, $cart->abandoned_cart_time ) . '</a></span><span> ' . $cart->total . '</span><br />';
			}
			$cart_display										 .= '</div>';
			$userinfofields[ 'abandoned_carts' ][ 'display' ]	 = $cart_display;
		}
	} catch ( Exception $e ) {
		error_log( $e->getMessage() );
	}
	?><h2><?php _e( 'Additional information', 'inkston-integration' ); ?></h2>

	<table class="form-table">
		<tbody><?php
			foreach ( $userinfofields as $key => $value ) {
				?><tr>
					<th><label><?php echo $value[ 'caption' ]; ?></label></th>
					<td>
						<?php
						$displayvalue = ' - ';
						if ( isset( $value[ 'display' ] ) && $value[ 'display' ] ) {
							$displayvalue = $value[ 'display' ];
						} elseif ( isset( $value[ 'data' ] ) && $value[ 'data' ] ) {
							$displayvalue = $value[ 'data' ];
							if ( is_array( $displayvalue ) ) {
								$displayvalue = recursive_filter_implode( ', ', $displayvalue );
							}
						}
						if ( isset( $value[ 'link' ] ) ) {
							printf( '<a href="%s">%s</a>', $value[ 'link' ], $displayvalue );
						} else {
							echo $displayvalue;
						}
						?>
					</td>
				</tr><?php
			}
			?></tbody>
	</table><?php
}

add_action( 'show_user_profile', 'ii_user_profile_info', 20 );
add_action( 'edit_user_profile', 'ii_user_profile_info', 20 );
/**
 * get additional interesting information about user..
 *
 * @param WP_User $user
 *
 * @return array of additional user information
 */
function ii_user_info( WP_User $user ) {
	$userId			 = $user->ID;
	$tKey			 = 'iiuser' . $userId;
	$date_format	 = get_option( 'date_format' ) . ' H:i:s';
	$user_score		 = 0;
	$userinfofields	 = get_transient( $tKey );
	//DEBUG: $userinfofields	 = 0;
	if ( ! $userinfofields ) {

		$registered		 = strtotime( $user->user_registered );
		$userinfofields	 = array(
			'registered' => array( 'caption'	 => __( 'Registered', 'inkston-integration' ),
				'data'		 => $registered,
				'display'	 => date_i18n( $date_format, $registered ) )
		);

		$socialLogin = get_user_option( 'thechamp_provider', $userId );
		if ( $socialLogin ) {
			$user_score += 1;
		}
		$userinfofields[ 'thechamp_provider' ] = array( 'caption'	 => __( 'Social Login', 'inkston-integration' ),
			'data'		 => $socialLogin
		);

		$last_update					 = get_user_option( 'last_update', $userId );
		$last_update_display			 = ($last_update) ? date_i18n( $date_format, $last_update ) : '';
		$userinfofields[ 'last_update' ] = array( 'caption'	 => __( 'Last Updated', 'inkston-integration' ),
			'data'		 => $last_update,
			'display'	 => $last_update_display
		);

		$wc_last_active			 = get_user_option( 'wc_last_active', $userId );
		$wc_last_active_display	 = '';
		if ( $wc_last_active ) {
			$wc_last_active_display	 = date_i18n( $date_format, ( $wc_last_active ) );
			$interval				 = date_diff( new \DateTime( '@' . $wc_last_active ), new \DateTime( '@' . time() ) );
			$months					 = intval( $interval->format( '%m' ) );
			$user_score				 += (12 - $months);
		}
		$userinfofields[ 'wc_last_active' ] = array( 'caption'	 => __( 'Last Active', 'inkston-integration' ),
			'data'		 => $wc_last_active,
			'display'	 => $wc_last_active_display
		);

		$userinfofields[ 'session_tokens' ] = array( 'caption' => __( 'Sessions', 'inkston-integration' ), 'data' => $user->get( 'session_tokens' ) );
		//interpret session dates
		if ( isset( $userinfofields[ 'session_tokens' ][ 'data' ] ) && $userinfofields[ 'session_tokens' ][ 'data' ] ) {
			$tokens				 = $userinfofields[ 'session_tokens' ][ 'data' ];
			$sessionDateString	 = '';
			if ( sizeof( $tokens ) > 0 ) {
				foreach ( $tokens as $key => $value ) {
					if ( isset( $value[ 'login' ] ) && $value[ 'login' ] ) {
						$sessionDateString .= date_i18n( $date_format, $value[ 'login' ] ) . ' ' . date( 'H:i:s', $value[ 'login' ] ) . ' - ';
					}
					if ( isset( $value[ 'expiration' ] ) && $value[ 'expiration' ] ) {
						$sessionDateString .= date_i18n( $date_format, $value[ 'expiration' ] ) . ' ' . date( 'H:i:s', $value[ 'expiration' ] ) . ' ';
					}
					$sessionDateString .= $key . ':' . recursive_filter_implode( ',', $value, true );
				}
			}
			$userinfofields[ 'session_tokens' ][ 'display' ] = $sessionDateString;
		}

		$mailpoet						 = ii_mailpoet_status( $userId );
		$userinfofields[ 'mailpoet' ]	 = array( 'caption'	 => __( 'Email status', 'inkston-integration' ),
			'data'		 => $mailpoet );
		//assign a score for mailpoet, taking into account bounce as a strong spam signup indicator..
		switch ( $mailpoet ) {
			case 'bounced':
				$user_score	 -= 10;
				break;
			case 'subscribed':
				$user_score	 += 1;
				break;
			case 'unconfirmed':  //actually, unclear why a registered user would be unconfirmed in mailpoet
				$user_score	 -= 3;
				break;
			case 'unsubscribed':
				$user_score	 -= 1;
				break;
		}

		//woocommerce orders
		$user_orders_link	 = ii_admin_customer_orders_link( $userId );
		$paying_customer	 = $user->get( 'paying_customer' );
		if ( $paying_customer ) {
			$user_score += 1;
		}
		$userinfofields[ 'paying_customer' ] = array( 'caption'	 => __( 'Paying Customer', 'inkston-integration' )
			, 'data'		 => $paying_customer
			, 'link'		 => $user_orders_link
			, 'display'	 => ($paying_customer) ? 'yes' : 'no'
		);

		if ( $paying_customer ) {
			$money_spent = ( class_exists( 'woocommerce' ) ) ? wc_get_customer_total_spent( $userId ) : $user->get( '_money_spent' );
			if ( $money_spent ) {
				$user_score							 += intval( $money_spent / 100 );
				$money_spent_display				 = ( class_exists( 'woocommerce' ) && $money_spent) ? wc_price( $money_spent ) : $money_spent;
				$userinfofields[ '_money_spent' ]	 = array( 'caption'	 => __( 'Money Spent', 'inkston-integration' )
					, 'data'		 => $money_spent
					, 'link'		 => $user_orders_link
					, 'display'	 => $money_spent_display
				);
				$userinfofields[ '_order_count' ]	 = array( 'caption'	 => __( 'Orders', 'inkston-integration' )
					, 'data'		 => $user->get( '_order_count' )
					, 'link'		 => $user_orders_link );
			}
		}

		$commment_count = ii_comment_count( $userId );
		if ( $commment_count ) {
			$user_score += intval( $commment_count );
		}
		$userinfofields[ 'commment_count' ] = array( 'caption'	 => __( 'Comments and Reviews', 'inkston-integration' )
			, 'data'		 => $commment_count
			, 'link'		 => network_site_url( '/wp-admin/edit-comments.php?user_id=' . $userId ) );

		$userinfofields[ 'wpbdp_listing' ] = array( 'caption'	 => __( 'Artist Listing', 'inkston-integration' )
			, 'data'		 => ii_community_posts( $userId, 'wpbdp_listing' )
			, 'link'		 => network_site_url( '/community/wp-admin/edit.php?post_type=wpbdp_listing&author=' . $userId )
		);


		$topic_count = $user->get( 'wp_2__bbp_topic_count' );
		if ( $topic_count ) {
			$user_score += intval( $topic_count );
		}
		$userinfofields[ 'wp_2__bbp_topic_count' ]	 = array( 'caption'	 => __( 'Topics', 'inkston-integration' )
			, 'data'		 => $topic_count
			, 'link'		 => network_site_url( '/community/forums/users/' . $user->user_nicename . '/topics/' ) );
		$reply_count								 = $user->get( 'wp_2__bbp_reply_count' );
		if ( $reply_count ) {
			$user_score += intval( $reply_count );
		}
		$userinfofields[ 'wp_2__bbp_reply_count' ]	 = array( 'caption'	 => __( 'Replies', 'inkston-integration' )
			, 'data'		 => $reply_count
			, 'link'		 => network_site_url( '/community/forums/users/' . $user->user_nicename . '/replies/' ) );
		$userinfofields[ 'wp_2__bbp_subscriptions' ] = array( 'caption'	 => __( 'Subscriptions', 'inkston-integration' )
			, 'data'		 => $user->get( 'wp_2__bbp_subscriptions' )
			, 'link'		 => network_site_url( '/community/forums/users/' . $user->user_nicename . '/subscriptions/' ) );

		$wp_2__bbp_last_posted = $user->get( 'wp_2__bbp_last_posted' );
		if ( $wp_2__bbp_last_posted ) {
			$userinfofields[ 'wp_2__bbp_last_posted' ] = array( 'caption'	 => __( 'Last Forum post', 'inkston-integration' ),
				'data'		 => $wp_2__bbp_last_posted,
				'display'	 => date_i18n( $date_format, $wp_2__bbp_last_posted ) );
		}
		$userinfofields[ 'wp_user_level' ] = array( 'caption' => __( 'wp_user_level', 'inkston-integration' ), 'data' => $user->get( 'wp_user_level' ) );

		$userinfofields[ 'user_score' ]	 = array( 'caption' => __( 'User Score', 'inkston-integration' ), 'data' => $user_score );
		$oldScore						 = $user->get( 'user_score' );
		if ( $oldScore != $user_score ) {
			update_user_meta( $userId, 'user_score', $user_score );
		}
		set_transient( $tKey, $userinfofields, 36000 );
	}
	return $userinfofields;
}

//add_filter( 'pre_get_users', array( $this, 'useFilters' ) );
//add_action( 'manage_users_extra_tablenav', array( $this, 'renderFilters' ) );

<?php
/*
 * handle links to special pages including login, account, registration
 *  shortcode [ink-login] returns a login form if the user is not logged in
 */
/**
 * gets the page id from id or slug, translated if polylang available
 *
 * @param string $page          id or slug of page to find
 *
 * @return int                  page id or false if no page found
 */
function inkGetPageID( $page ) {
	//get the page it, if $page is not already numeric
	if ( ! (is_numeric( $page )) ) {
		if ( is_string( $page ) ) {
			$args		 = array(
				'name'			 => $page,
				'post_type'		 => 'page',
				'post_status'	 => 'publish',
				'numberposts'	 => 1
			);
			$my_posts	 = get_posts( $args );
			if ( $my_posts ) {
				$page = $my_posts[ 0 ]->ID;
			} else {
				return false;
			}
		} else {
			$page = get_post( $page );
			if ( $page ) {
				$page = $page->ID;
			} else {
				return false;
			}
		}
	}

	//if polylang enabled, get page in right language
	if ( function_exists( 'pll_get_post' ) ) {
		$page = pll_get_post( $page );
	} else {
		$pageobj = get_page( $page );
		if ( ! $pageobj ) {
			return false;
		}
	}
	return $page; // returns the link
}

/**
 * gets the page for a specific blog..
 *
 * @param string $page          id or slug of page to find
 * @param int    $blogid        blog to search on
 *
 * @return int                  page id or false if no page found
 */
function inkGetPageURL( $post, $blogid ) {
	//switch blog if necessary
	$currentblogid = get_current_blog_id();
	if ( $currentblogid != $blogid ) {
		switch_to_blog( $blogid );
	}

	$post	 = inkGetPageID( $post );
	$url	 = ($post) ? get_page_link( $post ) : '';

	//restore blog if necessary
	if ( $currentblogid != $blogid ) {
		restore_current_blog();
	}

	return $url;
}

/*
 * IF on the inkston site and in a non-woocommerce sub-site
 * rewrite the standard login url to use the main woo account form..
 * This function should NOT detect referer parameters since it is deliberately called from
 * some external functions which deliberatly call without redirect and then add parameters back in..
 * .. so adding referer detection here was not a safe fallback..
 *
 * @param string $login_url    Default wordpress login url.
 * @param string $redirect     Path to redirect to on log in.
 * @param bool   $force_reauth Whether to force reauthorization
 *
 * @return string The login URL. Not HTML-encoded.
 */
function ink_login_url( $login_url, $redirect, $force_reauth ) {
	$ink_login_uri = '';
	if ( class_exists( 'woocommerce' ) ) {
		$ink_login_uri = wc_get_page_permalink( 'myaccount' );
	} elseif ( is_inkston() ) {
		//get the link for inkston only
		//TODO: allow child site with different branding to override this?
		$locale = get_locale();
		switch ( $locale ) {
			case 'fr_FR':
				$ink_login_uri	 = network_site_url( 'fr/mon-compte/' );
				break;
			case 'es_ES':
				$ink_login_uri	 = network_site_url( 'es/mi-cuenta/' );
				break;
			default:
				$ink_login_uri	 = network_site_url( 'my-account/' );
		}
	}
	//if we have a new link, recompose the parameters
	if ( $ink_login_uri != '' ) {
		$login_url = $ink_login_uri;
		if ( $redirect ) {
			$login_url = add_query_arg( 'redirect', urlencode( $redirect ), $login_url );
		}
		if ( $force_reauth ) {
			$login_url = add_query_arg( 'reauth', '1', $login_url );
		}
	}
	return $login_url;
}

add_filter( 'login_url', 'ink_login_url', 10, 3 );
/**
 * Filters the logout URL.
 *
 * @since 2.8.0
 *
 * @param string $logout_url The HTML-encoded logout URL.
 * @param string $redirect   Path to redirect to on logout.
 */
function ink_logout_url( $logout_url, $redirect ) {
	if ( class_exists( 'woocommerce' ) ) {
		return wc_logout_url();
		//return '/?customer-logout=true';
	}
	return $logout_url;
}

add_filter( 'logout_url', 'ink_logout_url', 10, 2 );
/**
 * Filters the user registration URL.
 *
 * @param string $register_url The user registration URL.
 */
function ink_register_url( $register_url ) {
	if ( class_exists( 'woocommerce' ) &&
	( get_option( 'woocommerce_enable_myaccount_registration' ) === 'yes' ) ) {
		return wc_get_page_permalink( 'myaccount' );
	}
	return $register_url;
}

add_filter( 'register_url', 'ink_register_url', 10, 1 );
/* for child family sites, allow cart link to main site cart */
function ink_cart_url() {
	$url = '';
	if ( class_exists( 'woocommerce' ) ) {
		$url = esc_url( wc_get_cart_url() );
	} elseif ( is_inkston() ) {
		$locale = get_locale();
		switch ( $locale ) {
			case 'fr_FR':
				$url = 'https://www.inkston.com/fr/panier/';
				break;
			case 'es_ES':
				$url = 'https://www.inkston.com/es/cesta-de-la-compra/';
				break;
			default:
				$url = 'https://www.inkston.com/cart/';
		}
	} else {
		error_log( 'Warning: cart url called on non-inkston site while woocommerce is not activated, no default provided.' );
	}
}

/**
 * Filters the dashboard URL for a user.
 *
 * @since 3.1.0
 *
 * @param string $url     The complete URL including scheme and path.
 * @param int    $user_id The user ID.
 * @param string $path    Path relative to the URL. Blank string if no path is specified.
 * @param string $scheme  Scheme to give the URL context. Accepts 'http', 'https', 'login',
 *                        'login_post', 'admin', 'relative' or null.
 */
function ink_user_dashboard_url( $url, $user_id, $path, $scheme ) {
	//IF woocommerce or bbPress are activated, return appropriate account url
	if ( class_exists( 'woocommerce' ) ) {
		return wc_get_account_endpoint_url( 'edit-account' );
	} elseif ( function_exists( 'bbp_user_profile_url' ) ) {
		return bbp_get_user_profile_url( $user_id );
	}
	return $url;
}

add_filter( 'user_dashboard_url', 'ink_user_dashboard_url', 10, 4 );




/*
 * implementation for shortcode [ink-login]
 * returns a login form if the user is not logged in
 */
function ink_login_form_shortcode() {
	if ( is_user_logged_in() ) {
		return '';
	}
	return wp_login_form( array( 'echo' => false ) );
}

add_shortcode( 'ink-login', 'ink_login_form_shortcode' );
/**
 * Display navigation to next/previous pages when applicable
 * used in inkston header.php
 * TODO: *MAY* not be needed in other themes, if needed would need to add this call, OR
 * TODO: find more generic theme hook method for implementation
 * TODO: deal with dependency on font-awesome .fa CSS
 */
function inkston_output_paging() {
	/* all posts pages */
	if ( is_single() && ! is_attachment() ) {
		/**
		 * add navigation for posts pages - works also for custom post types ie wooCommerce product
		 */
		?>
		<nav id="single-nav">
			<?php
			if ( (class_exists( 'woocommerce' )) && is_woocommerce() && is_product() ) {
				previous_post_link( '<div id="single-nav-right">%link</div>', '<i class="fa fa-chevron-left"></i>', true, '', 'product_cat' );
				next_post_link( '<div id="single-nav-left">%link</div>', '<i class="fa fa-chevron-right"></i>', true, '', 'product_cat' );
			} else {
				previous_post_link( '<div id="single-nav-right">%link</div>', '<i class="fa fa-chevron-left"></i>', true );
				next_post_link( '<div id="single-nav-left">%link</div>', '<i class="fa fa-chevron-right"></i>', true );
			}
			?>
		</nav><!-- /single-nav -->
		<?php
	} /* image media attachment pages - not in fact used currently, disabled by one of the plugins */ elseif ( is_attachment() ) {
		?>
		<nav id="single-nav">
			<div
				id="single-nav-right"><?php previous_image_link( '%link', '<i class="fa fa-chevron-left"></i>' ); ?></div>
			<div
				id="single-nav-left"><?php next_image_link( '%link', '<i class="fa fa-chevron-right"></i>' ); ?></div>
		</nav><!-- /single-nav -->
		<?php
	}
}

/**
 * get appropriate URL for an autho
 *
 * @param int        $userID The user ID.
 */
function getAuthorURL( $userID ) {
	$author_url = '';
	if ( function_exists( 'bbp_get_user_profile_url' ) ) {
		$author_url = bbp_get_user_profile_url( $userID );
	} else {
		$author_url = esc_url( get_author_posts_url( $userID ) );
	}
	return $author_url;
}

/**
 * Filters the comment author's URL.
 *
 * @param string     $url        The comment author's URL.
 * @param int        $comment_ID The comment ID.
 * @param WP_Comment $comment    The comment object.
 */
function getCommentUserURL( $url, $comment_ID, $comment ) {
	if ( $url ) {
		return $url;
	}
	if ( $comment ) {
		return getAuthorURL( $comment->user_id );
	}
}

//return apply_filters( 'get_comment_author_url', $url, $id, $comment );
add_filter( 'get_comment_author_url', 'getCommentUserURL', 10, 3 );

/*
 * get link for terms and conditions page
 */
function ink_tandc_link( $input ) {
	$url = '';
	//first, get standard terms link if available (woo 3.4 function)
	if ( function_exists( 'wc_terms_and_conditions_page_id' ) ) {
		$url = wc_terms_and_conditions_page_id();
	}

	if ( is_inkston() && $url = '' ) {
		$locale			 = get_locale();
		$currentblogid	 = get_current_blog_id();
		if ( $currentblogid == 2 ) {
			switch ( $locale ) {
				case 'fr_FR':
					$url = network_site_url( 'fr/inkston-community-directory-terms-conditions-2/' );
					break;
				case 'es_ES':
					$url = network_site_url( 'es/condiciones-directorio/' );
					break;
				default:
					$url = network_site_url( 'inkston-community-directory-terms-conditions/' );
			}
		} else {
			switch ( $locale ) {
				case 'fr_FR':
					$url = network_site_url( 'fr/portail/termes-et-conditions/' );
					break;
				case 'es_ES':
					$url = network_site_url( 'es/inkston-arte-y-artesania-oriental/terminos-y-condiciones/' );
					break;
				default:
					$url = network_site_url( '/inkston-oriental-arts-materials/conditions/' );
			}
		}
	}
	return ($url) ? $url : $input;
}

/*
 * get link for privacy page
 */
function ink_privacy_link( $input ) {
	$url = '';
	//first, get standard privacy link if available (wp 4.9.6 function)
	if ( function_exists( 'get_privacy_policy_url' ) ) {
		$url = get_privacy_policy_url();
	}
	if ( is_inkston() && $url = '' ) {
		$locale = get_locale();
		switch ( $locale ) {
			case 'fr_FR':
				$url = network_site_url( 'fr/portail/politique-de-confidentialite/' );
				break;
			case 'es_ES':
				$url = network_site_url( 'es/inkston-arte-y-artesania-oriental/politica-de-privacidad/' );
				break;
			default:
				$url = network_site_url( 'inkston-oriental-arts-materials/privacy-policy/' );
		}
	}
	return ($url) ? $url : $input;
}

<?php
/* 
 * super-socializer customization
 */

/**
 * Force add super socializer to login form 
 * 
 * @param string $content Content to display. Default empty.
 * @param array  $args    Array of login form arguments.
 * 
 * @return  string Content to display
 */
function ink_login_form_add_socializer( $content, $args ) {
    if (function_exists( 'the_champ_login_button')) {
        return $content . the_champ_login_shortcode(
                array(
                    'title' => __( 'Login or register with Facebook, LinkedIn, Google', 'inkston-integration')
            )) . '<div id="ink_login_message">' .
            __( 'Or use your Inkston login:', 'inkston-integration') .
            '</div>';
    } else {
        return $content;
    }
}
add_filter( 'login_form_top', 'ink_login_form_add_socializer', 10, 2);

/*
 * Override Supersocializer redirection urls
 * Supersocializer redirections have following problems:
 * 	the_champ_get_valid_url() doesn't allow redirect to the login page,
 * this means my-account redirects to home page after login instead of returning to account
 *   the_champ_get_login_redirection_url() doesn't handle full range of redirection urls
 * this is no longer correct..
function ink_ss_redirecturl( $redirectionUrl, $theChampLoginOptions, $user_ID, $twitterRedirect, $register ) {
	$url = '';
	if ( isset( $_REQUEST[ 'redirect' ] ) ) {
		$url = $_REQUEST[ 'redirect' ];
	}
	if ( isset( $_REQUEST[ 'redirect_to' ] ) ) {
		$url = $_REQUEST[ 'redirect_to' ];
	}
	if ( ! $url ) {
		$url = get_permalink();
	}
	if ( ! $url ) {
		if ( isset( $_SESSION[ 'super_socializer_facebook_redirect' ] ) ) {
			$url = $_SESSION[ 'super_socializer_facebook_redirect' ];
		}
	}
	if ( ! $url ) {
		$url = wp_get_referer();
	}
	if ( ! $url ) {
		$scheme	 = ( ! isset( $_SERVER[ 'HTTPS' ] ) || $_SERVER[ 'HTTPS' ] != "on") ? 'http' : 'https';
		$url	 = html_entity_decode( esc_url( $scheme . $_SERVER[ "HTTP_HOST" ] . $_SERVER[ "REQUEST_URI" ] ) );
	}

	//error_log( sprintf( "supersocializer redirection url %s changed to %s", $redirectionUrl, $url ) );

	if ( $url ) {
		$_SESSION[ 'super_socializer_facebook_redirect' ] = $url;
		return $url;
	}
	return $redirectionUrl;
}

add_filter( 'heateor_ss_login_redirection_url_filter', 'ink_ss_redirecturl', 10, 5 );
 */

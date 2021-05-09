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
	if ( function_exists( 'the_champ_login_button' ) ) {
		return $content . the_champ_login_shortcode(
		array(
			'title' => __( 'Login or register with Facebook, LinkedIn, Google', 'inkston-integration' )
		) ) . '<div id="ink_login_message">' .
		__( 'Or use your Inkston login:', 'inkston-integration' ) .
		'</div>';
	} else {
		return $content;
	}
}

add_filter( 'login_form_top', 'ink_login_form_add_socializer', 10, 2 );

/*
 * Override Supersocializer redirection urls
 * Supersocializer redirections have following problems:
 * 	the_champ_get_valid_url() doesn't allow redirect to the login page,
 * this means my-account redirects to home page after login instead of returning to account
 *  the_champ_get_login_redirection_url() doesn't handle full range of redirection urls
 */
function ink_ss_redirecturl( $redirectionUrl, $theChampLoginOptions, $user_ID, $twitterRedirect, $register ) {
//	ink_debug( 'in ink_ss_redirecturl' );
//	if ( isset( $theChampLoginOptions[ $option . '_redirection' ] ) ) {
//		if ( $theChampLoginOptions[ $option . '_redirection' ] == 'same' ) {

	$url = '';
	//CHANGE:JM:ALLOW REFERER URL
	if ( isset( $_REQUEST[ 'redirect' ] ) ) {
		$url = $_REQUEST[ 'redirect' ];
	}
	if ( isset( $_REQUEST[ 'redirect_to' ] ) ) {
		$url = $_REQUEST[ 'redirect_to' ];
	}
	if ( ! $url ) {
		$referurl = wp_get_referer();        
		if ( $referurl ) {
			if ( strpos( $referurl, 'redirect' ) ) {
				$url = getQueryParameter( $referurl, 'redirect' );
                ink_debug( 'extracted redirect from wp_get_referer ' . $referurl . ' as "' . $url . '"');
			} else {
                ink_debug( 'discarded wp_get_referer ' . $referurl );
			}
		}        
	}
    if ( ! $url ) {
        if ( strpos( $redirectionUrl, 'redirect' ) ) {
            $url = getQueryParameter( $redirectionUrl, 'redirect' );
            ink_debug( 'extracted redirect from $redirectionUrl ' . $redirectionUrl . ' as "' . $url . '"');
        } 
    }
	if ( $redirectionUrl != $url && $url != '' ) {
        ink_debug( sprintf( "inkston changed supersocializer redirection url %s to %s", $redirectionUrl, $url ) );
		$redirectionUrl = $url;
	}
	/* the validation function resets url back to home page .. ..
	  $redirectionUrl = the_champ_get_valid_url( $url );
	  if ( $redirectionUrl != $url ) {
	  ink_debug( sprintf( "the_champ_get_valid_url changed redirection url %s to %s", $url, $redirectionUrl ) );
	  }
	 */
	return $redirectionUrl;
}

add_filter( 'heateor_ss_login_redirection_url_filter', 'ink_ss_redirecturl', 10, 5 );

/*
 * used to get redirection query parameter
 */
function getQueryParameter( $url, $param ) {
	$parsedUrl = parse_url( $url );
	if ( array_key_exists( 'query', $parsedUrl ) ) {
		parse_str( $parsedUrl[ 'query' ], $queryParameters );
		if ( array_key_exists( $param, $queryParameters ) ) {
			return $queryParameters[ $param ];
		}
	}
}

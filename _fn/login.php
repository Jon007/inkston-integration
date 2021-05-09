<?php

/*
 * login form customizations
 */

/*
 * add link to logo in login form
 */
function ink_login_logo_url() {
    //return 'https://www.inkston.com/';
    //return home_url();
    return network_site_url();
}

add_filter( 'login_headerurl', 'ink_login_logo_url' );

/*
 * custom title for login form
 */
function ink_login_logo_url_title() {
    //return 'Inkston.com';
    return get_bloginfo( 'name' );
}

add_filter( 'login_headertitle', 'ink_login_logo_url_title' );
/*
 * adds a wrapper div around the login form
 */
function ink_login_header() {
    echo ( '<div id="site-main">');
}

add_filter( 'login_header', 'ink_login_header' );

/*
 * closes login form wrapper div
 */
function ink_login_footer() {
    echo ( '</div>');
}

add_filter( 'login_footer', 'ink_login_footer' );


/*
 * adds password honeypot to standard wp login form
 */
function ink_login_honey() {
?>
<div class="wp-pwd" style="display:notone">
    <input type="password" name="password2" id="user_pass2" class="input password-input" value="oldpassword" size="20">
    <button type="button" class="button button-secondary wp-hide-pw hide-if-no-js" data-toggle="0" aria-label="Show password">
        <span class="dashicons dashicons-visibility" aria-hidden="true"></span>
    </button>
</div>
<?php 
}
function ink_login_honey_plus_errors($errors){
    ink_login_honey(); 
	$errmsg = $errors->get_error_message( 'registration-error-bad-email' );
	if ( $errmsg ) {
		echo '<p class="error">' . $errmsg . '</p>';
	}    
}
add_action('resetpass_form', 'ink_login_honey', 10, 0);
add_action('login_form', 'ink_login_honey', 10, 0);
add_action('signup_extra_fields', 'ink_login_honey_plus_errors', 10, 1);



/*
 * woocommerce integration for BanHammer spam control from
 * https://github.com/Ipstenu/ban-hammer/wiki#woocommerce
 */
add_filter( 'woocommerce_registration_errors', 'woocommerce_banhammer_validation', 10, 3 );
function woocommerce_banhammer_validation( $validation_errors, $username, $email ) {
	//use condition to skip checks on checkout - is_account_page() does not work
	//because account is actually created on ajax
    $error_message = __('Error: sorry there was a problem with the details provided, please contact us.', 'inkston-integration');
	if ( ! is_ajax() ) {
		if ( class_exists( 'BanHammer' ) ) {
            $error_message = (new BanHammer)->options[ 'message' ];
			if ( (new BanHammer )->banhammer_drop( $username, $email, $validation_errors ) ) {
				ink_debug( sprintf(
				__( 'Registration email %1$s for user %1$s blocked by BanHammer rule', 'inkston-integration' ), $email, $username ) );
				return new WP_Error( 'registration-error-bad-email', $error_message);
			}
		}
		if ( isset( $_REQUEST[ 'password2' ] ) ) {
			if ( $_REQUEST[ 'password2' ] != 'oldpassword' ) {
                ink_debug( sprintf(
                __( 'Registration email %1$s for user %1$s blocked due to password2 honeypot', 'inkston-integration' ), $email, $username ) );
				return new WP_Error( 'registration-error-bad-email', $error_message );
			}
		} else {
			ink_debug( sprintf(
			__( 'Registration email %1$s for user %1$s blocked due to missing password2 ', 'inkston-integration' ), $email, $username ) );
			return new WP_Error( 'registration-error-bad-email', $error_message );
		}
		if ( isset( $_REQUEST[ 'password' ] ) ) {
			if ( $_REQUEST[ 'password' ] == '' ) {
				return new WP_Error( 'registration-error-bad-email', __( 'Please enter a password!', 'inkston-integration' ) );
			}
		}
	} else {
		ink_debug( sprintf(
		__( 'Registration email %1$s for user %1$s accepted without further checks as registration is in ajax (not a registration page submission)', 'inkston-integration' ), $email, $username ) );
	}
	return $validation_errors;
}

function ink_authenticate_username_password( $user_or_error, $username, $password ) {
    /*if ( $user_or_error instanceof WP_User ) {
        return $user_or_error;
    }*/
    if ($username == ""){
        return $user_or_error;
    }
    return woocommerce_banhammer_validation( $user_or_error, $username, $password );
}
add_filter( 'authenticate', 'ink_authenticate_username_password', 999, 3 );


	/**
	 * only filter available for mu signup
     * Filters the validated user registration details.
	 *
	 * This does not allow you to override the username or email of the user during
	 * registration. The values are solely used for validation and error handling.
	 *
	 * @since MU (3.0.0)
	 *
	 * @param array $result {
	 *     The array of user name, email, and the error messages.
	 *
	 *     @type string   $user_name     Sanitized and unique username.
	 *     @type string   $orig_username Original username.
	 *     @type string   $user_email    User email address.
	 *     @type WP_Error $errors        WP_Error object containing any errors found.
	 * }
	 */
function ink_authenticate_signup($params){
    $inkcheck = woocommerce_banhammer_validation( null, null, null );   
    if ( is_wp_error( $inkcheck ) ) {
		$params['errors'] = $inkcheck;
	}
    return $params;
}
//return apply_filters( 'wpmu_validate_user_signup', $result );
add_filter( 'wpmu_validate_user_signup', 'ink_authenticate_signup', 999, 1 );

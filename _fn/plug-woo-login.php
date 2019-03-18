<?php
/*
 * enhanced login/registration redirection handling for woocommerce
 */


/*
 * Add redirect fields to login/registration forms
 */
function ink_redirect_field() {
	$referer = '';
	if ( isset( $_POST[ 'redirect' ] ) ) {
		$referer = $_POST[ 'redirect' ];
	} elseif ( isset( $_REQUEST[ 'redirect' ] ) ) {
		$referer = $_REQUEST[ 'redirect' ];
	} elseif ( isset( $_REQUEST[ 'redirect_to' ] ) ) {
		$referer = $_REQUEST[ 'redirect_to' ];
	}
//    if ($referer == ''){
//        $referer = wp_get_raw_referer();
//    }
	?><input type="hidden" name="redirect" value="<?php
	echo ($referer);
	?>" /><?php
}

add_action( 'woocommerce_login_form_end', 'ink_redirect_field' );
add_action( 'woocommerce_register_form_end', 'ink_redirect_field' );

/*
 * Allow redirect to previous page after registration
 * @param string $redirect     this is the registration screen itself.
 * @param string $account_page ie My Account.
 *
 */
function ink_redirect_registration( $referer ) {
	if ( isset( $_POST[ 'redirect' ] ) && $_POST[ 'redirect' ] != '' ) {
		$referer = $_POST[ 'redirect' ];
	} elseif ( isset( $_REQUEST[ 'redirect' ] ) && $_REQUEST[ 'redirect' ] != '' ) {
		$referer = $_REQUEST[ 'redirect' ];
	} else {   //wc_customer_edit_account_url() works, BUT when just signed up, customer has not received original password and cannot reset
		// but if they have just signed up with passsword they dont need to edit it..
		if ( class_exists( 'woocommerce' ) ) {
			$referer = wc_get_page_permalink( 'myaccount' );
		}
	}
	return $referer;
}

add_filter( 'woocommerce_registration_redirect', 'ink_redirect_registration', 10, 1 );

/*
 * allow user to sign up with password
 * 		$new_customer_data = apply_filters(
 * 				'woocommerce_new_customer_data', array(
 * 				'user_login' => $username,
 * 				'user_pass'  => $password,
 * 				'user_email' => $email,
 * 				'role'       => 'customer',
 * 			)
 * 		);
 */
function ink_allow_user_register_with_pwd( $woocommerce_new_customer_data ) {
	//if a password is posted (because we added it to the form),
	//then use it even though woo is automatically generating passwords
	if ( 'yes' === get_option( 'woocommerce_registration_generate_password' ) && isset( $_POST[ 'password' ] ) ) {
		$woocommerce_new_customer_data[ 'user_pass' ] = $_POST[ 'password' ];
	}
	if ( 'yes' === get_option( 'woocommerce_registration_generate_username' ) && isset( $_POST[ 'username' ] ) ) {
		$woocommerce_new_customer_data[ 'user_login' ] = $_POST[ 'username' ];
	}
	return $woocommerce_new_customer_data;
}

add_filter( 'woocommerce_new_customer_data', 'ink_allow_user_register_with_pwd' );

/*
 * add password validation script if applicable
 */
function ink_add_pwd_script() {
	if ( is_account_page() ) {
		// Password strength meter. Load in checkout, account login and edit account page.
		if ( ( 'no' != get_option( 'woocommerce_registration_generate_password' ) && ! is_user_logged_in() ) || is_edit_account_page() || is_lost_password_page() ) {
			wp_enqueue_script( 'wc-password-strength-meter' );
		}
	}
}

add_action( 'wp_enqueue_scripts', 'ink_add_pwd_script' );

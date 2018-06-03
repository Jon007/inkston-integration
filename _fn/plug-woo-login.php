<?php
/* 
 * enhanced login/registration redirection handling for woocommerce
 */


/*
 * Add redirect fields to login/registration forms
 */
function ink_redirect_field()
{
    $referer = '';
    if (isset($_POST['redirect'])) {
        $referer = $_POST['redirect'];
    }elseif (isset($_REQUEST['redirect'])) {
        $referer = $_REQUEST['redirect'];
    }elseif (isset($_REQUEST['redirect_to'])) {
        $referer = $_REQUEST['redirect_to'];
    } 
//    if ($referer == ''){
//        $referer = wp_get_raw_referer();
//    }

    ?><input type="hidden" name="redirect" value="<?php
    echo ($referer);

    ?>" /><?php
}
add_action( 'woocommerce_login_form_end', 'ink_redirect_field');
add_action( 'woocommerce_register_form_end', 'ink_redirect_field');

/*
 * Allow redirect to previous page after registration
 * @param string $redirect     this is the registration screen itself.
 * @param string $account_page ie My Account.
 * 
 */
function ink_redirect_registration($referer)
{
    if (isset($_POST['redirect'])) {
        $referer = $_POST['redirect'];
    }
    if (isset($_REQUEST['redirect'])) {
        $referer = $_REQUEST['redirect'];
    }
    return $referer;
}
add_filter( 'woocommerce_registration_redirect', 'ink_redirect_registration', 10, 1);


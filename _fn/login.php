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


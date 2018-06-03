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
function ink_login_form_add_socializer($content, $args)
{
    if (function_exists( 'the_champ_login_button')) {
        return $content . the_champ_login_shortcode(
                array(
                    'title' => __( 'Login or register with Facebook, LinkedIn, Google', 'photoline-inkston')
            )) . '<div id="ink_login_message">' .
            __( 'Or use your Inkston login:', 'photoline-inkston') .
            '</div>';
    } else {
        return $content;
    }
}
add_filter( 'login_form_top', 'ink_login_form_add_socializer', 10, 2);




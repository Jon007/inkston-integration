<?php

/*
 * contact form 7 customizations
 */

/* disable contact form 7 scripts */
add_filter( 'wpcf7_load_css', '__return_false' );
add_filter( 'wpcf7_load_js', '__return_false' );

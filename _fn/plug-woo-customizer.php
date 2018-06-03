<?php

/*
 * These functions belong in the customizer as they affect the display
 */

/* set number of products per page */
//add_filter( 'loop_shop_per_page', function ( $cols ) {return 15;}, 20);
//no longer needed, replace by option in customizer 'woocommerce_catalog_rows'

// set number or products per row ex 4
//add_filter( 'loop_shop_columns', function () {return 5;}, 20);
//no longer needed, replaced by get_option( 'woocommerce_catalog_columns');
